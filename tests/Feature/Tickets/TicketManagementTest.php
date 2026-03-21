<?php

namespace Tests\Feature\Tickets;

use App\Models\Customer;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_can_create_ticket_into_backlog(): void
    {
        $support = User::factory()->create();
        $category = TicketCategory::factory()->create();
        $response = $this->actingAs($support)->post('/tickets', [
            'customer_name' => 'Cong ty Le Van A',
            'requester_contact_method' => 'Telegram',
            'priority' => Ticket::PRIORITY_HIGH,
            'title' => 'Không đăng nhập được wifi',
            'description' => 'Thiết bị báo sai mật khẩu dù đã đổi nhiều lần.',
            'category_name' => $category->name,
        ]);

        $ticket = Ticket::query()->first();
        $customer = Customer::query()->first();

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertNotNull($customer);
        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'customer_id' => $customer->id,
            'requester_name' => 'Cong ty Le Van A',
            'requester_contact_method' => 'Telegram',
            'priority' => Ticket::PRIORITY_HIGH,
            'status' => Ticket::STATUS_OPEN,
            'assignee_id' => null,
            'created_by' => $support->id,
        ]);
        $this->assertMatchesRegularExpression('/^TK-\d{6}-001$/', (string) $ticket->ticket_code);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'action_type' => 'ticket_created',
        ]);
    }

    public function test_ticket_creation_requires_mandatory_fields(): void
    {
        $support = User::factory()->create();

        $response = $this->actingAs($support)->from('/tickets/create')->post('/tickets', []);

        $response->assertRedirect('/tickets/create');
        $response->assertSessionHasErrors([
            'customer_name',
            'title',
            'description',
            'category_name',
        ]);
    }

    public function test_support_can_create_ticket_for_existing_customer_without_creating_duplicate_customer(): void
    {
        $support = User::factory()->create();
        $category = TicketCategory::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Cong ty Hien Co',
            'phone' => '028123123',
            'email' => 'hello@hienco.local',
            'representative_name' => 'Tran Hien Co',
            'representative_phone' => '0908777666',
            'license_count' => 18,
        ]);

        $response = $this->actingAs($support)->post('/tickets', [
            'customer_id' => $customer->id,
            'requester_contact_method' => 'Email',
            'priority' => Ticket::PRIORITY_NORMAL,
            'title' => 'Loi kich hoat phan mem',
            'description' => 'Can kiem tra lai thong tin kich hoat tren may moi.',
            'category_name' => $category->name,
        ]);

        $ticket = Ticket::query()->first();

        $response->assertRedirect(route('tickets.show', $ticket));
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'customer_id' => $customer->id,
            'requester_name' => 'Cong ty Hien Co',
            'requester_contact_method' => 'Email',
            'priority' => Ticket::PRIORITY_NORMAL,
        ]);
    }

    public function test_ticket_creation_matches_existing_or_creates_new_category_and_contact_method(): void
    {
        $support = User::factory()->create();
        Tag::query()->create([
            'type' => Tag::TYPE_CONTACT_METHOD,
            'code' => 'telegram',
            'name' => 'Telegram',
            'is_active' => true,
        ]);
        TicketCategory::factory()->create([
            'name' => 'Phần cứng',
            'code' => 'phan-cung',
        ]);

        $this->actingAs($support)->post('/tickets', [
            'customer_name' => 'Cong ty Match Tag',
            'requester_contact_method' => 'telegram',
            'priority' => Ticket::PRIORITY_HIGH,
            'title' => 'Lỗi bàn phím',
            'description' => 'Bàn phím không nhận.',
            'category_name' => 'phần cứng',
        ])->assertRedirect();

        $this->actingAs($support)->post('/tickets', [
            'customer_name' => 'Cong ty New Tag',
            'requester_contact_method' => 'Signal',
            'priority' => Ticket::PRIORITY_NORMAL,
            'title' => 'Cấp lại quyền truy cập',
            'description' => 'Cần thêm quyền mới.',
            'category_name' => 'Quyền truy cập',
        ])->assertRedirect();

        $this->assertDatabaseHas('tags', [
            'type' => Tag::TYPE_CONTACT_METHOD,
            'name' => 'Signal',
        ]);
        $this->assertDatabaseHas('ticket_categories', [
            'name' => 'Quyền truy cập',
        ]);
        $this->assertDatabaseHas('tickets', [
            'title' => 'Lỗi bàn phím',
            'requester_contact_method' => 'Telegram',
            'priority' => Ticket::PRIORITY_HIGH,
        ]);
        $this->assertDatabaseHas('tickets', [
            'title' => 'Cấp lại quyền truy cập',
            'requester_contact_method' => 'Signal',
            'priority' => Ticket::PRIORITY_NORMAL,
        ]);
    }

    public function test_ticket_list_can_be_filtered(): void
    {
        $support = User::factory()->create();
        $software = TicketCategory::factory()->create(['name' => 'Phần mềm']);
        $network = TicketCategory::factory()->create(['name' => 'Mạng']);
        $assignee = User::factory()->create(['name' => 'Nguyen Assignee']);

        Ticket::factory()->create([
            'title' => 'Cài lại Office',
            'category_id' => $software->id,
            'status' => Ticket::STATUS_OPEN,
            'created_by' => $support->id,
        ]);

        Ticket::factory()->create([
            'title' => 'Sự cố VPN',
            'category_id' => $network->id,
            'status' => Ticket::STATUS_IN_PROGRESS,
            'assignee_id' => $assignee->id,
            'created_by' => $support->id,
        ]);

        $response = $this->actingAs($support)->get('/tickets?status=In%20Progress&category_id='.$network->id.'&assignee_id='.$assignee->id.'&search=VPN');

        $response->assertOk();
        $response->assertSee('Sự cố <mark>VPN</mark>', false);
        $response->assertDontSeeText('Cài lại Office');
    }

    public function test_ticket_list_supports_fuzzy_search_with_typo_tolerance(): void
    {
        $support = User::factory()->create();
        $category = TicketCategory::factory()->create();

        Ticket::factory()->create([
            'title' => 'Su co VPN van phong',
            'requester_name' => 'Cong ty Sao Mai',
            'category_id' => $category->id,
            'created_by' => $support->id,
        ]);

        Ticket::factory()->create([
            'title' => 'Cai dat may in',
            'requester_name' => 'Cong ty Mat Troi',
            'category_id' => $category->id,
            'created_by' => $support->id,
        ]);

        $response = $this->actingAs($support)->get('/tickets?search=vpn vanphng');

        $response->assertOk();
        $response->assertViewHas('tickets', fn ($tickets) => $tickets->total() === 1);
        $response->assertDontSeeText('Cai dat may in');
        $response->assertSee('<mark>VPN</mark>', false);
    }

    public function test_ticket_search_falls_back_to_database_search_when_meilisearch_is_not_configured(): void
    {
        config()->set('scout.driver', 'database');

        $support = User::factory()->create();
        $category = TicketCategory::factory()->create();

        Ticket::factory()->create([
            'title' => 'VPN mat ket noi',
            'requester_name' => 'Cong ty Sao Nam',
            'category_id' => $category->id,
            'created_by' => $support->id,
        ]);

        $response = $this->actingAs($support)->get('/tickets?search=vpn ketnoi');

        $response->assertOk();
        $response->assertSeeText('VPN mat ket noi');
    }

    public function test_support_can_claim_ticket_and_update_status(): void
    {
        $support = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'status' => Ticket::STATUS_OPEN,
            'assignee_id' => null,
        ]);

        $this->actingAs($support)->post(route('tickets.claim', $ticket))
            ->assertRedirect();

        $ticket->refresh();

        $this->assertSame($support->id, $ticket->assignee_id);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->status);

        $this->actingAs($support)->patch(route('tickets.status.update', $ticket), [
            'status' => Ticket::STATUS_RESOLVED,
        ])->assertRedirect();

        $ticket->refresh();

        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'action_type' => 'ticket_claimed',
        ]);
        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'action_type' => 'status_changed',
        ]);
    }

    public function test_support_can_add_comment_and_public_view_key_is_read_only(): void
    {
        $support = User::factory()->create([
            'name' => 'Tran Support',
            'username' => 'tran.support',
            'email' => 'tran.support@internal.local',
            'is_active' => true,
        ]);
        $ticket = Ticket::factory()->create([
            'created_by' => $support->id,
        ]);

        $this->actingAs($support)->post(route('tickets.comments.store', $ticket), [
            'content' => 'Đã kiểm tra và đang cập nhật máy người dùng.',
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'author_id' => $support->id,
        ]);

        auth()->logout();

        $response = $this->get(route('tickets.show', ['ticket' => $ticket, 'view_key' => $ticket->view_key]));

        $response->assertOk();
        $response->assertSee('Chế độ xem read-only');
        $response->assertSee('Copy link');
        $response->assertDontSee('name="content"', false);
        $response->assertDontSee('Nhận ticket này');
    }

    public function test_ticket_detail_shows_actor_name_and_username_in_activity_history(): void
    {
        $support = User::factory()->create([
            'name' => 'Le Operator',
            'username' => 'le.operator',
            'email' => 'le.operator@internal.local',
            'is_active' => true,
        ]);
        $ticket = Ticket::factory()->create([
            'created_by' => $support->id,
        ]);

        $this->actingAs($support)->patch(route('tickets.status.update', $ticket), [
            'status' => Ticket::STATUS_IN_PROGRESS,
        ])->assertRedirect();

        $response = $this->actingAs($support)->get(route('tickets.show', $ticket));

        $response->assertOk();
        $response->assertSee('Khách hàng');
        $response->assertSee('le.operator');
        $response->assertDontSee('le.operator@internal.local');
    }

    public function test_inactive_user_cannot_interact_with_ticket_routes(): void
    {
        $support = User::factory()->create([
            'is_active' => false,
            'status' => 'inactive',
        ]);
        $ticket = Ticket::factory()->create();

        $this->actingAs($support)
            ->post(route('tickets.comments.store', $ticket), ['content' => 'Should fail'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseMissing('ticket_comments', [
            'ticket_id' => $ticket->id,
            'content' => 'Should fail',
        ]);
    }

    public function test_support_can_bulk_update_selected_tickets_to_resolved(): void
    {
        $support = User::factory()->create();
        $firstTicket = Ticket::factory()->create([
            'status' => Ticket::STATUS_OPEN,
            'resolved_at' => null,
        ]);
        $secondTicket = Ticket::factory()->create([
            'status' => Ticket::STATUS_IN_PROGRESS,
            'resolved_at' => null,
        ]);

        $this->actingAs($support)->patch(route('tickets.bulk-status.update'), [
            'ticket_ids' => [$firstTicket->id, $secondTicket->id],
            'status' => Ticket::STATUS_RESOLVED,
        ])->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $firstTicket->id,
            'status' => Ticket::STATUS_RESOLVED,
        ]);
        $this->assertDatabaseHas('tickets', [
            'id' => $secondTicket->id,
            'status' => Ticket::STATUS_RESOLVED,
        ]);
        $this->assertDatabaseCount('ticket_activities', 2);
    }

    public function test_ticket_list_shows_related_handlers_from_claim_comment_and_status_updates(): void
    {
        $creator = User::factory()->create(['name' => 'Nguyen Creator']);
        $claimHandler = User::factory()->create(['name' => 'Tran Claim']);
        $supportAgent = User::factory()->create(['name' => 'Le Support']);
        $ticket = Ticket::factory()->create([
            'created_by' => $creator->id,
            'assignee_id' => null,
            'status' => Ticket::STATUS_OPEN,
        ]);

        $this->actingAs($claimHandler)->post(route('tickets.claim', $ticket))->assertRedirect();
        $this->actingAs($supportAgent)->post(route('tickets.comments.store', $ticket), [
            'content' => 'Đã kiểm tra lịch sử sự cố của máy.',
        ])->assertRedirect();
        $this->actingAs($supportAgent)->patch(route('tickets.status.update', $ticket), [
            'status' => Ticket::STATUS_RESOLVED,
        ])->assertRedirect();

        $response = $this->actingAs($creator)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSee('Tran Claim');
        $response->assertSee('Le Support');
        $response->assertDontSee('Phụ trách chính: Tran Claim');
    }

    public function test_ticket_list_is_sorted_with_newest_ticket_first(): void
    {
        $support = User::factory()->create();
        $olderTicket = Ticket::factory()->create([
            'title' => 'Ticket cũ hơn',
            'created_by' => $support->id,
            'created_at' => now()->subDay(),
        ]);
        $newerTicket = Ticket::factory()->create([
            'title' => 'Ticket mới hơn',
            'created_by' => $support->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($support)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            $newerTicket->ticket_code,
            $olderTicket->ticket_code,
        ], false);
    }

    public function test_ticket_list_supports_per_page_options_and_keeps_selection(): void
    {
        $support = User::factory()->create();

        Ticket::factory()->count(30)->create([
            'created_by' => $support->id,
        ]);

        $response = $this->actingAs($support)->get(route('tickets.index', [
            'per_page' => 25,
        ]));

        $response->assertOk();
        $response->assertViewHas('tickets', fn ($tickets) => $tickets->perPage() === 25);
        $response->assertSee('name="per_page"', false);
        $response->assertSee('value="25" selected', false);
        $response->assertSee('per_page=25', false);
        $response->assertSee('page=2', false);
    }

    public function test_ticket_code_uses_daily_sequence_format(): void
    {
        $support = User::factory()->create();
        $date = now()->setDate(2026, 3, 26)->setTime(8, 30);

        $firstTicket = Ticket::factory()->create([
            'created_by' => $support->id,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
        $secondTicket = Ticket::factory()->create([
            'created_by' => $support->id,
            'created_at' => $date->copy()->addHour(),
            'updated_at' => $date->copy()->addHour(),
        ]);
        $nextDayTicket = Ticket::factory()->create([
            'created_by' => $support->id,
            'created_at' => $date->copy()->addDay(),
            'updated_at' => $date->copy()->addDay(),
        ]);

        $this->assertSame('TK-260326-001', $firstTicket->ticket_code);
        $this->assertSame('TK-260326-002', $secondTicket->ticket_code);
        $this->assertSame('TK-260327-001', $nextDayTicket->ticket_code);
    }
}
