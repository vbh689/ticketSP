<?php

namespace App\Support\Search;

use App\Models\Customer;
use App\Support\FuzzySearch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CustomerSearchService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 8): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        if ($this->shouldUseMeilisearch()) {
            try {
                $meilisearchResults = $this->searchWithMeilisearch($query, $limit);

                if ($meilisearchResults->isNotEmpty()) {
                    return $meilisearchResults;
                }
            } catch (\Throwable $exception) {
                Log::warning('Customer search fell back to database search.', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $this->searchWithDatabase($query, $limit);
    }

    private function shouldUseMeilisearch(): bool
    {
        return config('scout.driver') === 'meilisearch'
            && filled(config('scout.meilisearch.host'));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchWithMeilisearch(string $query, int $limit): Collection
    {
        $response = Customer::search($query)
            ->options([
                'limit' => $limit,
                'attributesToHighlight' => ['name', 'representative_name', 'phone', 'email'],
                'highlightPreTag' => '<mark>',
                'highlightPostTag' => '</mark>',
            ])
            ->raw();

        return collect($response['hits'] ?? [])
            ->map(fn (array $hit): array => $this->transformMeilisearchHit($hit))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchWithDatabase(string $query, int $limit): Collection
    {
        return Customer::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'phone',
                'email',
                'representative_name',
                'license_count',
            ])
            ->map(function (Customer $customer) use ($query): ?array {
                $score = FuzzySearch::score([
                    $customer->name,
                    $customer->representative_name,
                    $customer->phone,
                    $customer->email,
                ], $query);

                if ($score === null) {
                    return null;
                }

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'name_html' => FuzzySearch::highlightHtml($customer->name, $query),
                    'contact_preview' => $this->contactPreview([
                        'representative_name' => $customer->representative_name,
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                    ]),
                    'contact_html' => $this->contactHtml([
                        'representative_name' => $customer->representative_name,
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                    ], $query),
                    'license_preview' => $customer->license_count !== null ? (string) $customer->license_count : 'Chưa cập nhật',
                    'selected_label' => $this->selectedLabel([
                        'name' => $customer->name,
                        'representative_name' => $customer->representative_name,
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                        'license_count' => $customer->license_count,
                    ]),
                    'score' => $score,
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $hit
     * @return array<string, mixed>
     */
    private function transformMeilisearchHit(array $hit): array
    {
        $formatted = $hit['_formatted'] ?? [];

        return [
            'id' => (int) $hit['id'],
            'name' => $hit['name'],
            'name_html' => $formatted['name'] ?? e((string) $hit['name']),
            'contact_preview' => $this->contactPreview($hit),
            'contact_html' => $this->contactHtml($formatted ?: $hit),
            'license_preview' => isset($hit['license_count']) && $hit['license_count'] !== null ? (string) $hit['license_count'] : 'Chưa cập nhật',
            'selected_label' => $this->selectedLabel($hit),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function contactPreview(array $payload): string
    {
        return $payload['representative_name']
            ?? $payload['phone']
            ?? $payload['email']
            ?? 'Chưa có';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function contactHtml(array $payload, ?string $query = null): string
    {
        $parts = array_values(array_filter([
            $payload['representative_name'] ?? null,
            $payload['phone'] ?? null,
            $payload['email'] ?? null,
        ]));

        if ($parts === []) {
            return e('Chưa có thông tin liên hệ');
        }

        if ($query === null) {
            return implode(' | ', array_map(fn (mixed $part): string => (string) $part, $parts));
        }

        return implode(' | ', array_map(
            fn (mixed $part): string => FuzzySearch::highlightHtml((string) $part, $query),
            $parts
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function selectedLabel(array $payload): string
    {
        $parts = array_filter([
            isset($payload['representative_name']) && $payload['representative_name'] ? 'Đại diện: '.$payload['representative_name'] : null,
            isset($payload['phone']) && $payload['phone'] ? 'SĐT công ty: '.$payload['phone'] : null,
            isset($payload['email']) && $payload['email'] ? 'Email: '.$payload['email'] : null,
            isset($payload['license_count']) && $payload['license_count'] !== null ? 'License: '.$payload['license_count'] : null,
        ]);

        return trim(($payload['name'] ?? '').($parts ? ' | '.implode(' | ', $parts) : ''));
    }
}
