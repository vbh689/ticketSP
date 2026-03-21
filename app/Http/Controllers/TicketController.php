<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Support\TicketActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
            ->with(['category', 'assignee', 'creator', 'activities.actor'])
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
            'customers' => Customer::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'address',
                    'phone',
                    'email',
                    'representative_name',
                    'representative_phone',
                    'license_count',
                    'notes',
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:1000'],
            'customer_phone' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_representative_name' => ['nullable', 'string', 'max:255'],
            'customer_representative_phone' => ['nullable', 'string', 'max:255'],
            'customer_license_count' => ['nullable', 'integer', 'min:0'],
            'customer_notes' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_id' => ['required', 'exists:ticket_categories,id'],
        ], [], [
            'customer_id' => 'khách hàng',
            'customer_name' => 'tên khách hàng',
            'customer_address' => 'địa chỉ khách hàng',
            'customer_phone' => 'điện thoại khách hàng',
            'customer_email' => 'email khách hàng',
            'customer_representative_name' => 'nhân viên đại diện',
            'customer_representative_phone' => 'điện thoại nhân viên đại diện',
            'customer_license_count' => 'số lượng license',
            'customer_notes' => 'ghi chú khách hàng',
            'title' => 'tiêu đề',
            'description' => 'mô tả',
            'category_id' => 'loại ticket',
        ]);

        $customer = $validated['customer_id'] ?? null
            ? Customer::query()->findOrFail($validated['customer_id'])
            : Customer::create([
                'name' => $validated['customer_name'],
                'address' => $validated['customer_address'] ?? null,
                'phone' => $validated['customer_phone'] ?? null,
                'email' => $validated['customer_email'] ?? null,
                'representative_name' => $validated['customer_representative_name'] ?? null,
                'representative_phone' => $validated['customer_representative_phone'] ?? null,
                'license_count' => $validated['customer_license_count'] ?? null,
                'notes' => $validated['customer_notes'] ?? null,
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
