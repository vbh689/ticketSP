<?php

namespace App\Support\Search;

use App\Models\Ticket;
use App\Support\FuzzySearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorResult;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;

class TicketSearchService
{
    public function search(array $filters, Request $request): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        $ticketQuery = Ticket::query()
            ->with(['category', 'assignee', 'creator', 'activities.actor'])
            ->filter($filters)
            ->latest();

        if ($search === '') {
            return $ticketQuery->paginate(12)->withQueryString();
        }

        if ($this->shouldUseMeilisearch() && ($filters['assignee_id'] ?? null) !== 'unassigned') {
            try {
                return $this->searchWithMeilisearch($filters, $request);
            } catch (\Throwable $exception) {
                Log::warning('Ticket search fell back to database search.', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $this->searchWithDatabase($ticketQuery, $search, $request);
    }

    private function shouldUseMeilisearch(): bool
    {
        return config('scout.driver') === 'meilisearch'
            && filled(config('scout.meilisearch.host'));
    }

    private function searchWithMeilisearch(array $filters, Request $request): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $page = Paginator::resolveCurrentPage();
        $perPage = 12;

        $options = [
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
            'attributesToHighlight' => ['ticket_code', 'title', 'requester_name', 'requester_contact'],
            'highlightPreTag' => '<mark>',
            'highlightPostTag' => '</mark>',
            'sort' => ['created_at_timestamp:desc'],
        ];

        if ($filterExpression = $this->buildFilterExpression($filters)) {
            $options['filter'] = $filterExpression;
        }

        $response = Ticket::search($search)
            ->options($options)
            ->raw();

        $hits = collect($response['hits'] ?? []);
        $ticketIds = $hits->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        $ticketsById = Ticket::query()
            ->with(['category', 'assignee', 'creator', 'activities.actor'])
            ->whereIn('id', $ticketIds)
            ->get()
            ->keyBy('id');

        $tickets = $hits
            ->map(function (array $hit) use ($ticketsById): ?Ticket {
                /** @var Ticket|null $ticket */
                $ticket = $ticketsById->get((int) $hit['id']);

                if (! $ticket) {
                    return null;
                }

                $formatted = $hit['_formatted'] ?? [];
                $ticket->highlighted_ticket_code = $formatted['ticket_code'] ?? e((string) $ticket->ticket_code);
                $ticket->highlighted_title = $formatted['title'] ?? e((string) $ticket->title);
                $ticket->highlighted_requester_name = $formatted['requester_name'] ?? e((string) $ticket->requester_name);
                $ticket->highlighted_requester_contact = $formatted['requester_contact'] ?? e((string) ($ticket->requester_contact ?: 'Chưa có liên hệ'));

                return $ticket;
            })
            ->filter()
            ->values();

        return new PaginatorResult(
            $tickets,
            (int) ($response['estimatedTotalHits'] ?? $tickets->count()),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function searchWithDatabase(Builder $ticketQuery, string $search, Request $request): LengthAwarePaginator
    {
        $candidates = $ticketQuery->limit(250)->get();

        $matchedTickets = $candidates
            ->map(function (Ticket $ticket) use ($search): Ticket {
                $score = FuzzySearch::score([
                    $ticket->ticket_code,
                    $ticket->title,
                    $ticket->requester_name,
                    $ticket->requester_contact,
                    $ticket->description,
                    $ticket->category?->name,
                ], $search);

                $ticket->search_score = $score;
                $ticket->highlighted_ticket_code = FuzzySearch::highlightHtml((string) $ticket->ticket_code, $search);
                $ticket->highlighted_title = FuzzySearch::highlightHtml((string) $ticket->title, $search);
                $ticket->highlighted_requester_name = FuzzySearch::highlightHtml((string) $ticket->requester_name, $search);
                $ticket->highlighted_requester_contact = FuzzySearch::highlightHtml((string) ($ticket->requester_contact ?: 'Chưa có liên hệ'), $search);

                return $ticket;
            })
            ->filter(fn (Ticket $ticket) => $ticket->search_score !== null)
            ->sortByDesc('search_score')
            ->values();

        $page = Paginator::resolveCurrentPage();
        $perPage = 12;

        return new PaginatorResult(
            $matchedTickets->forPage($page, $perPage)->values(),
            $matchedTickets->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function buildFilterExpression(array $filters): ?string
    {
        $expressions = [];

        if ($status = ($filters['status'] ?? null)) {
            $expressions[] = sprintf('status = "%s"', addslashes($status));
        }

        if ($categoryId = ($filters['category_id'] ?? null)) {
            $expressions[] = sprintf('category_id = %d', (int) $categoryId);
        }

        if ($assigneeId = ($filters['assignee_id'] ?? null)) {
            $expressions[] = sprintf('assignee_id = %d', (int) $assigneeId);
        }

        return $expressions === [] ? null : implode(' AND ', $expressions);
    }
}
