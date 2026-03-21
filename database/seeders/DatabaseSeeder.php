<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = collect([
            ['code' => 'tai-khoan', 'name' => 'Tài khoản'],
            ['code' => 'phan-mem', 'name' => 'Phần mềm'],
            ['code' => 'phan-cung', 'name' => 'Phần cứng'],
            ['code' => 'mang', 'name' => 'Mạng'],
            ['code' => 'khac', 'name' => 'Khác'],
        ])->map(fn (array $category) => TicketCategory::query()->firstOrCreate(
            ['code' => $category['code']],
            ['name' => $category['name'], 'is_active' => true]
        ));

        collect([
            ['type' => Tag::TYPE_CONTACT_METHOD, 'code' => 'email', 'name' => 'Email'],
            ['type' => Tag::TYPE_CONTACT_METHOD, 'code' => 'phone', 'name' => 'Phone'],
            ['type' => Tag::TYPE_CONTACT_METHOD, 'code' => 'telegram', 'name' => 'Telegram'],
        ])->each(fn (array $tag) => Tag::query()->firstOrCreate(
            ['type' => $tag['type'], 'code' => $tag['code']],
            ['name' => $tag['name'], 'is_active' => true]
        ));

        $customers = collect([
            [
                'name' => 'Cong Ty ABC',
                'address' => '123 Nguyen Hue, Quan 1, TP.HCM',
                'phone' => '02838229999',
                'email' => 'support@abc.local',
                'representative_name' => 'Nguyen Van An',
                'representative_phone' => '0903000001',
                'license_count' => 25,
                'notes' => 'Khach hang mau cho ticket VPN va su co mang.',
            ],
            [
                'name' => 'Cong Ty XYZ',
                'address' => '88 Le Loi, Quan 1, TP.HCM',
                'phone' => '02839118888',
                'email' => 'it@xyz.local',
                'representative_name' => 'Tran Thi Binh',
                'representative_phone' => '0903000002',
                'license_count' => 40,
                'notes' => 'Khach hang mau cho ticket cai dat phan mem.',
            ],
        ])->mapWithKeys(fn (array $customer) => [
            $customer['name'] => Customer::query()->updateOrCreate(
                ['name' => $customer['name']],
                $customer
            ),
        ]);

        $supportLead = User::query()->updateOrCreate([
            'email' => 'support.lead@internal.local',
        ], [
            'name' => 'SP Lead 1',
            'username' => 'support.lead',
            'phone' => '0901000001',
            'department' => 'IT Support',
            'primary_contact_method' => 'Telegram',
            'is_manager' => true,
            'is_active' => true,
            'password' => 'password',
            'status' => 'active',
        ]);

        $supportAgent = User::query()->updateOrCreate([
            'email' => 'support.agent@internal.local',
        ], [
            'name' => 'SP Agent 1',
            'username' => 'support.agent',
            'phone' => '0901000002',
            'department' => 'IT Support',
            'primary_contact_method' => 'Phone',
            'is_manager' => false,
            'is_active' => true,
            'password' => 'password',
            'status' => 'active',
        ]);

        if (Ticket::query()->exists()) {
            return;
        }

        $abcCompany = $customers->get('Cong Ty ABC');
        $xyzCompany = $customers->get('Cong Ty XYZ');

        $networkTicket = Ticket::factory()->create([
            'customer_id' => $abcCompany?->id,
            'created_by' => $supportLead->id,
            'category_id' => $categories[3]->id,
            'requester_name' => $abcCompany?->name ?? 'Cong Ty ABC',
            'requester_contact' => $this->buildRequesterContact($abcCompany),
            'requester_contact_method' => 'Telegram',
            'title' => 'Máy tính không vào được VPN',
            'description' => 'Người dùng báo không đăng nhập được VPN từ sáng nay.',
            'status' => Ticket::STATUS_OPEN,
        ]);

        Ticket::factory()->create([
            'customer_id' => $xyzCompany?->id,
            'created_by' => $supportLead->id,
            'assignee_id' => $supportAgent->id,
            'category_id' => $categories[1]->id,
            'requester_name' => $xyzCompany?->name ?? 'Cong Ty XYZ',
            'requester_contact' => $this->buildRequesterContact($xyzCompany),
            'requester_contact_method' => 'Phone',
            'title' => 'Cài lại Microsoft Office',
            'description' => 'Thiết bị mới cần cài lại bộ Office và kích hoạt license.',
            'status' => Ticket::STATUS_IN_PROGRESS,
        ]);

        $networkTicket->activities()->create([
            'actor_id' => $supportLead->id,
            'action_type' => 'ticket_created',
            'action_detail' => 'Tạo ticket mẫu ban đầu.',
        ]);
    }

    private function buildRequesterContact(?Customer $customer): ?string
    {
        if (! $customer) {
            return null;
        }

        $parts = array_filter([
            $customer->representative_name ? "Đại diện: {$customer->representative_name}" : null,
            $customer->representative_phone ? "SĐT đại diện: {$customer->representative_phone}" : null,
            $customer->phone ? "SĐT công ty: {$customer->phone}" : null,
            $customer->email ? "Email: {$customer->email}" : null,
        ]);

        return $parts ? implode(' | ', $parts) : null;
    }
}
