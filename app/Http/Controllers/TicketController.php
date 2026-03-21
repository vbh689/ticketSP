<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Support\TicketActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(Ticket::statuses())],
            'category_id' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'string'],
        ]);

        $tickets = Ticket::query()
            ->with(['category', 'assignee', 'creator'])
            ->filter($filters)
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'assignees' => User::query()->where('status', 'active')->orderBy('name')->get(),
            'statuses' => Ticket::statuses(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', [
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_contact' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:ticket_categories,id'],
        ], [], [
            'requester_name' => 'người yêu cầu',
            'requester_contact' => 'liên hệ',
            'title' => 'tiêu đề',
            'description' => 'mô tả',
            'category_id' => 'loại ticket',
        ]);

        $ticket = Ticket::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        TicketActivityLogger::log(
            $ticket,
            $request->user(),
            'ticket_created',
            'Tạo ticket mới trong backlog.'
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', "Đã tạo ticket {$ticket->ticket_code}.");
    }

    public function show(Request $request, Ticket $ticket): View|RedirectResponse
    {
        $ticket->load([
            'category',
            'creator',
            'assignee',
            'comments.author',
            'activities.actor',
        ]);

        $user = $request->user();
        $hasValidViewKey = hash_equals($ticket->view_key, (string) $request->query('view_key'));

        if (! $user && ! $hasValidViewKey) {
            return redirect()->route('login');
        }

        return view('tickets.show', [
            'ticket' => $ticket,
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => Ticket::statuses(),
            'isReadOnly' => ! $user,
            'shareUrl' => route('tickets.show', ['ticket' => $ticket, 'view_key' => $ticket->view_key]),
        ]);
    }

    public function claim(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->assignee_id) {
            return back()->withErrors([
                'claim' => 'Ticket này đã có người phụ trách.',
            ]);
        }

        $ticket->update([
            'assignee_id' => $request->user()->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);

        TicketActivityLogger::log(
            $ticket->fresh(),
            $request->user(),
            'ticket_claimed',
            'Nhận ticket và chuyển sang trạng thái In Progress.'
        );

        return back()->with('status', 'Bạn đã nhận ticket này.');
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Ticket::statuses())],
        ]);

        $newStatus = $validated['status'];
        $updates = ['status' => $newStatus];

        if ($newStatus === Ticket::STATUS_RESOLVED) {
            $updates['resolved_at'] = now();
            $updates['closed_at'] = null;
        } elseif ($newStatus === Ticket::STATUS_CLOSED) {
            $updates['closed_at'] = now();

            if (! $ticket->resolved_at) {
                $updates['resolved_at'] = now();
            }
        } else {
            $updates['resolved_at'] = null;
            $updates['closed_at'] = null;
        }

        $ticket->update($updates);

        TicketActivityLogger::log(
            $ticket->fresh(),
            $request->user(),
            'status_changed',
            "Cập nhật trạng thái sang {$newStatus}."
        );

        return back()->with('status', 'Đã cập nhật trạng thái ticket.');
    }
}
