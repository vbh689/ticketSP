<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class SupportTicketsDemoSeeder extends Seeder
{
    private const CSV_PATH = 'docs/SupportTickets_Demo.csv';

    private const HEADER_TICKET_CODE = 'Mã Ticket';

    private const HEADER_CREATED_DATE = "Ngày tạo\n(dd/MM/yyyy)";

    private const HEADER_COMPANY = 'Tên công ty/ cửa hàng';

    private const HEADER_REQUESTER = 'Tên khách hàng';

    private const HEADER_CONTACT = 'Liên hệ';

    private const HEADER_CONTENT = 'Nội dung yêu cầu';

    private const HEADER_PRIORITY = 'Mức độ ưu tiên';

    private const HEADER_ASSIGNEE = 'Người xử lý';

    private const HEADER_RECEIVED_TIME = "Thời gian tiếp nhận \n(Giờ:Phút)";

    private const HEADER_COMPLETED_TIME = "Thời gian hoàn thành\n(Giờ:Phút)";

    private const HEADER_STATUS = 'Trạng thái';

    private const HEADER_NOTE = 'Ghi chú';

    private const HEADER_CSAT = 'CSAT';

    public function run(): void
    {
        $filePath = base_path(self::CSV_PATH);

        if (! is_file($filePath)) {
            throw new RuntimeException("Không tìm thấy file CSV demo tại {$filePath}");
        }

        $category = TicketCategory::query()->firstOrCreate(
            ['code' => 'phan-mem'],
            ['name' => 'Phần mềm', 'is_active' => true]
        );

        $importUser = User::query()->updateOrCreate([
            'email' => 'demo.importer@internal.local',
        ], [
            'name' => 'Demo Importer',
            'username' => 'demo.importer',
            'department' => 'IT Support',
            'primary_contact_method' => 'Email',
            'is_manager' => false,
            'is_active' => true,
            'password' => 'password',
            'status' => 'active',
        ]);

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Không thể mở file CSV demo tại {$filePath}");
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            throw new RuntimeException('File CSV demo không có dữ liệu.');
        }

        $header = array_map(fn ($value) => $this->cleanHeader($value), $header);

        while (($row = fgetcsv($handle)) !== false) {
            $record = $this->mapRow($header, $row);

            if (! $record) {
                continue;
            }

            $this->seedTicketRecord($record, $category, $importUser);
        }

        fclose($handle);
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, string|null>  $row
     * @return array<string, string>|null
     */
    private function mapRow(array $header, array $row): ?array
    {
        $record = [];

        foreach ($header as $index => $column) {
            $record[$column] = trim((string) ($row[$index] ?? ''));
        }

        if (($record[self::HEADER_TICKET_CODE] ?? '') === '') {
            return null;
        }

        return $record;
    }

    /**
     * @param  array<string, string>  $record
     */
    private function seedTicketRecord(array $record, TicketCategory $category, User $importUser): void
    {
        $ticketCode = $record[self::HEADER_TICKET_CODE];
        $createdAt = $this->parseDateTime(
            $record[self::HEADER_CREATED_DATE] ?? '',
            $record[self::HEADER_RECEIVED_TIME] ?? ''
        ) ?? now();
        $completedAt = $this->parseDateTime(
            $record[self::HEADER_CREATED_DATE] ?? '',
            $record[self::HEADER_COMPLETED_TIME] ?? ''
        );
        $status = $this->mapStatus($record[self::HEADER_STATUS] ?? '');
        $priority = $this->mapPriority($record[self::HEADER_PRIORITY] ?? '');
        $companyName = $this->cleanText($record[self::HEADER_COMPANY] ?? '');
        $requesterName = $this->cleanText($record[self::HEADER_REQUESTER] ?? '') ?: ($companyName ?: 'Khách hàng demo');
        $contactMethod = $this->cleanText($record[self::HEADER_CONTACT] ?? '');
        $content = $this->cleanText($record[self::HEADER_CONTENT] ?? '');
        $note = $this->cleanText($record[self::HEADER_NOTE] ?? '');
        $csat = $this->cleanText($record[self::HEADER_CSAT] ?? '');

        $customer = $this->resolveCustomer($companyName, $requesterName, $contactMethod, $note);
        $assignee = $this->resolveAssignee($record[self::HEADER_ASSIGNEE] ?? '');
        $description = $this->buildDescription($content, $note, $csat);
        $title = Str::limit($content !== '' ? $content : 'Yêu cầu hỗ trợ demo', 255, '...');

        $ticket = Ticket::query()->firstOrNew([
            'ticket_code' => $ticketCode,
        ]);

        $ticket->fill([
            'customer_id' => $customer->id,
            'requester_name' => $requesterName,
            'requester_contact' => $contactMethod !== '' ? $contactMethod : null,
            'requester_contact_method' => $contactMethod !== '' ? $contactMethod : null,
            'priority' => $priority,
            'title' => $title,
            'description' => $description,
            'category_id' => $category->id,
            'status' => $status,
            'assignee_id' => $assignee?->id,
            'created_by' => $importUser->id,
            'resolved_at' => in_array($status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true) ? ($completedAt ?? $createdAt) : null,
            'closed_at' => $status === Ticket::STATUS_CLOSED ? ($completedAt ?? $createdAt) : null,
        ]);

        $ticket->forceFill([
            'ticket_code' => $ticketCode,
            'view_key' => $ticket->view_key ?: Str::random(40),
            'created_at' => $createdAt,
            'updated_at' => $completedAt ?? $createdAt,
        ])->saveQuietly();
    }

    private function resolveCustomer(string $companyName, string $requesterName, string $contactMethod, string $note): Customer
    {
        $customerName = $companyName !== '' ? $companyName : $requesterName;

        $customer = Customer::query()->firstOrNew([
            'name' => $customerName,
        ]);

        $customer->fill(array_filter([
            'representative_name' => $requesterName !== $customerName ? $requesterName : null,
            'notes' => $note !== '' ? $note : null,
        ], fn ($value) => $value !== null && $value !== ''));

        if ($customer->phone === null && preg_match('/^\d[\d\s.+-]*$/u', $contactMethod)) {
            $customer->phone = $contactMethod;
        }

        $customer->saveQuietly();

        return $customer;
    }

    private function resolveAssignee(string $assigneeName): ?User
    {
        $assigneeName = $this->cleanText(Str::before($assigneeName, ','));

        if ($assigneeName === '') {
            return null;
        }

        $baseUsername = Str::slug($assigneeName, '.');
        $username = $baseUsername !== '' ? $baseUsername : 'demo-agent';
        $email = "{$username}@internal.local";

        return User::query()->updateOrCreate([
            'email' => $email,
        ], [
            'name' => $assigneeName,
            'username' => $username,
            'department' => 'IT Support',
            'primary_contact_method' => 'Phone',
            'is_manager' => false,
            'is_active' => true,
            'password' => 'password',
            'status' => 'active',
        ]);
    }

    private function mapStatus(string $status): string
    {
        return match ($this->cleanText($status)) {
            'Đã giải quyết' => Ticket::STATUS_RESOLVED,
            'Đang xử lý', 'Chờ khách phản hồi', 'Chờ kỹ thuật xử lý' => Ticket::STATUS_IN_PROGRESS,
            'Đã Hủy' => Ticket::STATUS_CLOSED,
            default => Ticket::STATUS_OPEN,
        };
    }

    private function mapPriority(string $priority): string
    {
        return match ($this->cleanText($priority)) {
            'Thấp' => Ticket::PRIORITY_LOW,
            'Khẩn cấp', 'Cao' => Ticket::PRIORITY_HIGH,
            default => Ticket::PRIORITY_NORMAL,
        };
    }

    private function buildDescription(string $content, string $note, string $csat): string
    {
        $sections = array_filter([
            $content,
            $note !== '' ? "Ghi chú: {$note}" : null,
            $csat !== '' ? "CSAT: {$csat}" : null,
        ]);

        return implode("\n\n", $sections);
    }

    private function parseDateTime(string $date, string $time): ?Carbon
    {
        $date = $this->cleanText($date);
        $time = $this->cleanText($time);

        if ($date === '') {
            return null;
        }

        $format = $time !== '' ? 'd/m/Y H:i' : 'd/m/Y';
        $value = $time !== '' ? "{$date} {$time}" : $date;

        return Carbon::createFromFormat($format, $value, config('app.timezone'));
    }

    private function cleanHeader(?string $value): string
    {
        return str_replace("\xEF\xBB\xBF", '', trim((string) $value));
    }

    private function cleanText(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }
}
