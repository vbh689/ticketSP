<?php

namespace Database\Seeders;

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

        $supportLead = User::query()->updateOrCreate([
            'email' => 'support.lead@internal.local',
        ], [
            'name' => 'Nguyen Support',
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
            'name' => 'Tran Helpdesk',
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

        $networkTicket = Ticket::factory()->create([
            'created_by' => $supportLead->id,
            'category_id' => $categories[3]->id,
            'requester_name' => 'Le Minh',
            'requester_contact' => 'minh.le@company.local',
            'title' => 'Máy tính không vào được VPN',
            'description' => 'Người dùng báo không đăng nhập được VPN từ sáng nay.',
            'status' => Ticket::STATUS_OPEN,
        ]);

        Ticket::factory()->create([
            'created_by' => $supportLead->id,
            'assignee_id' => $supportAgent->id,
            'category_id' => $categories[1]->id,
            'requester_name' => 'Pham Lan',
            'requester_contact' => 'lan.pham@company.local',
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
}
