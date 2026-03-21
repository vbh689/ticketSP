<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Support\Search\TicketSearchService;
use App\Support\TicketActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketSearchService $ticketSearch,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(Ticket::statuses())],
            'category_id' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'string'],
        ]);

        $tickets = $this->ticketSearch->search($filters, $request);

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
        $selectedCustomerId = session()->getOldInput('customer_id');

        return view('tickets.create', [
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedCustomer' => $selectedCustomerId
                ? Customer::query()->find($selectedCustomerId, [
                    'id',
                    'name',
                    'phone',
                    'email',
                    'representative_name',
                    'license_count',
                ])
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:ticket_categories,id'],
        ], [], [
            'customer_id' => 'khách hàng',
            'customer_name' => 'tên khách hàng',
            'title' => 'tiêu đề',
            'description' => 'mô tả',
            'category_id' => 'loại ticket',
        ]);

        $customer = $validated['customer_id'] ?? null
            ? Customer::query()->findOrFail($validated['customer_id'])
            : Customer::create([
                'name' => $validated['customer_name'],
            ]);

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'requester_name' => $customer->name,
            'requester_contact' => $this->buildRequesterContact($customer),
            ...Arr::only($validated, ['title', 'description', 'category_id']),
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

        $user = $request->user()?->is_active ? $request->user() : null;
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

        $ticket->update($this->buildStatusPayload($ticket, $newStatus));

        TicketActivityLogger::log(
            $ticket->fresh(),
            $request->user(),
            'status_changed',
            "Cập nhật trạng thái sang {$newStatus}."
        );

        return back()->with('status', 'Đã cập nhật trạng thái ticket.');
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'exists:tickets,id'],
            'status' => ['required', Rule::in(Ticket::statuses())],
        ], [], [
            'ticket_ids' => 'danh sách ticket',
            'status' => 'trạng thái',
        ]);

        $tickets = Ticket::query()
            ->whereIn('id', $validated['ticket_ids'])
            ->get();

        foreach ($tickets as $ticket) {
            $ticket->update($this->buildStatusPayload($ticket, $validated['status']));

            TicketActivityLogger::log(
                $ticket->fresh(),
                $request->user(),
                'status_changed',
                "Cập nhật trạng thái sang {$validated['status']} bằng thao tác hàng loạt."
            );
        }

        return back()->with('status', 'Đã cập nhật trạng thái cho các ticket đã chọn.');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(Ticket $ticket, string $newStatus): array
    {
        $updates = ['status' => $newStatus];

        if ($newStatus === Ticket::STATUS_RESOLVED) {
            $updates['resolved_at'] = now();
            $updates['closed_at'] = null;
        } elseif ($newStatus === Ticket::STATUS_CLOSED) {
            $updates['closed_at'] = now();
            $updates['resolved_at'] = $ticket->resolved_at ?: now();
        } else {
            $updates['resolved_at'] = null;
            $updates['closed_at'] = null;
        }

        return $updates;
    }

    private function buildRequesterContact(Customer $customer): ?string
    {
        $parts = array_filter([
            $customer->representative_name ? "Đại diện: {$customer->representative_name}" : null,
            $customer->representative_phone ? "SĐT đại diện: {$customer->representative_phone}" : null,
            $customer->phone ? "SĐT công ty: {$customer->phone}" : null,
            $customer->email ? "Email: {$customer->email}" : null,
        ]);

        return $parts ? implode(' | ', $parts) : null;
    }
}
