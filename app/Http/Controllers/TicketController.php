<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Support\Search\TicketSearchService;
use App\Support\TicketActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'per_page' => ['nullable', Rule::in(TicketSearchService::perPageOptions())],
        ]);

        $tickets = $this->ticketSearch->search($filters, $request);

        return view('tickets.index', [
            'tickets' => $tickets,
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'assignees' => User::query()->where('status', 'active')->orderBy('name')->get(),
            'statuses' => Ticket::statuses(),
            'filters' => $filters,
            'exportPeriods' => $this->exportPeriods(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $periods = $this->exportPeriods();

        $validated = $request->validate([
            'period' => ['required', Rule::in(array_keys($periods))],
        ]);

        $selectedPeriod = $periods[$validated['period']];
        $tickets = $this->buildExportQuery($validated['period'])->get();
        $filename = sprintf(
            'tickets-%s-%s.csv',
            $selectedPeriod['slug'],
            now()->format('Ymd-His')
        );

        return response()->streamDownload(function () use ($tickets): void {
            $handle = fopen('php://output', 'wb');

            if (! $handle) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Mã ticket',
                'Ngày tạo',
                'Thời gian resolved',
                'Trạng thái',
                'Ưu tiên',
                'Loại ticket',
                'Khách hàng',
                'Thông tin khách hàng',
                'Phương thức liên hệ',
                'Tiêu đề',
                'Mô tả',
                'Người phụ trách',
                'Người tạo',
            ]);

            foreach ($tickets as $ticket) {
                fputcsv($handle, [
                    $ticket->ticket_code,
                    $ticket->created_at?->format('d/m/Y H:i'),
                    $ticket->resolved_at?->format('d/m/Y H:i'),
                    $ticket->status,
                    $ticket->priority,
                    $ticket->category?->name,
                    $ticket->requester_name,
                    $ticket->requester_contact,
                    $ticket->requester_contact_method,
                    $ticket->title,
                    $ticket->description,
                    $ticket->assignee?->name,
                    $ticket->creator?->name,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create(): View
    {
        $selectedCustomerId = session()->getOldInput('customer_id');

        return view('tickets.create', [
            'categories' => TicketCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'contactMethods' => Tag::query()
                ->forType(Tag::TYPE_CONTACT_METHOD)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'priorities' => Ticket::priorities(),
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
            'requester_contact_method' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', Rule::in(Ticket::priorities())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_name' => ['required', 'string', 'max:255'],
        ], [], [
            'customer_id' => 'khách hàng',
            'customer_name' => 'tên khách hàng',
            'requester_contact_method' => 'phương thức liên hệ',
            'priority' => 'ưu tiên',
            'title' => 'tiêu đề',
            'description' => 'mô tả',
            'category_name' => 'loại ticket',
        ]);

        $customer = $validated['customer_id'] ?? null
            ? Customer::query()->findOrFail($validated['customer_id'])
            : Customer::create([
                'name' => $validated['customer_name'],
            ]);

        $category = $this->resolveCategory($validated['category_name']);
        $contactMethod = $this->resolveContactMethod($validated['requester_contact_method'] ?? null);
        $priority = $validated['priority'] ?? Ticket::PRIORITY_NORMAL;

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'requester_name' => $customer->name,
            'requester_contact' => $this->buildRequesterContact($customer),
            'requester_contact_method' => $contactMethod,
            'priority' => $priority,
            ...Arr::only($validated, ['title', 'description']),
            'category_id' => $category->id,
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

    /**
     * @return array<string, array{label: string, slug: string}>
     */
    private function exportPeriods(): array
    {
        return [
            'today' => ['label' => 'Hôm nay', 'slug' => 'hom-nay'],
            'this_week' => ['label' => 'Tuần này', 'slug' => 'tuan-nay'],
            'this_month' => ['label' => 'Tháng này', 'slug' => 'thang-nay'],
            'last_month' => ['label' => 'Tháng trước', 'slug' => 'thang-truoc'],
            'last_7_days' => ['label' => '7 ngày gần nhất', 'slug' => '7-ngay-gan-nhat'],
            'last_30_days' => ['label' => '30 ngày gần nhất', 'slug' => '30-ngay-gan-nhat'],
            'all' => ['label' => 'Tất cả ticket', 'slug' => 'tat-ca-ticket'],
        ];
    }

    private function buildExportQuery(string $period): \Illuminate\Database\Eloquent\Builder
    {
        $query = Ticket::query()
            ->with(['category', 'assignee', 'creator'])
            ->latest();

        [$from, $to] = $this->resolveExportRange($period);

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        return $query;
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveExportRange(string $period): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'all' => [null, null],
        };
    }

    private function resolveCategory(string $name): TicketCategory
    {
        $normalizedName = \Illuminate\Support\Str::of($name)->trim()->squish()->toString();

        $existingCategory = TicketCategory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->first();

        if ($existingCategory) {
            return $existingCategory;
        }

        return TicketCategory::create([
            'name' => $normalizedName,
            'code' => \Illuminate\Support\Str::slug($normalizedName),
            'is_active' => true,
        ]);
    }

    private function resolveContactMethod(?string $name): ?string
    {
        $normalizedName = \Illuminate\Support\Str::of((string) $name)->trim()->squish()->toString();

        if ($normalizedName === '') {
            return null;
        }

        $existingTag = Tag::query()
            ->forType(Tag::TYPE_CONTACT_METHOD)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->first();

        if ($existingTag) {
            return $existingTag->name;
        }

        $displayName = \Illuminate\Support\Str::title($normalizedName);

        Tag::create([
            'type' => Tag::TYPE_CONTACT_METHOD,
            'name' => $displayName,
            'code' => \Illuminate\Support\Str::slug($displayName),
            'is_active' => true,
        ]);

        return $displayName;
    }

}
