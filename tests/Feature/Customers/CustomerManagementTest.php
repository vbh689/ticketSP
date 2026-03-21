<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Http\Controllers\SearchMaintenanceController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\EngineManager;
use Mockery;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_and_update_customer(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);

        $this->actingAs($manager)->post(route('customers.store'), [
            'name' => 'Cong ty ABC',
            'address' => '123 Nguyen Hue, Quan 1, TP.HCM',
            'phone' => '02812345678',
            'email' => 'contact@abc.local',
            'representative_name' => 'Nguyen Dai Dien',
            'representative_phone' => '0903000111',
            'license_count' => '25',
            'notes' => 'Khach dang dung goi ho tro co ban.',
        ])->assertRedirect(route('customers.index'));

        $customer = Customer::query()->where('email', 'contact@abc.local')->firstOrFail();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Cong ty ABC',
            'representative_name' => 'Nguyen Dai Dien',
            'license_count' => 25,
        ]);

        $this->actingAs($manager)->patch(route('customers.update', $customer), [
            'name' => 'Cong ty ABC Updated',
            'address' => '456 Le Loi, Quan 1, TP.HCM',
            'phone' => '02887654321',
            'email' => 'support@abc.local',
            'representative_name' => 'Tran Dai Dien',
            'representative_phone' => '0903999222',
            'license_count' => '30',
            'notes' => 'Cap nhat them license va doi dau moi lien he.',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Cong ty ABC Updated',
            'email' => 'support@abc.local',
            'representative_name' => 'Tran Dai Dien',
            'license_count' => 30,
        ]);
    }

    public function test_customer_index_displays_customer_fields(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);
        $customer = Customer::query()->create([
            'name' => 'Cong ty Sao Bien',
            'address' => 'Da Nang',
            'phone' => '0236111222',
            'email' => 'hello@saobien.local',
            'representative_name' => 'Le Thanh',
            'representative_phone' => '0904555666',
            'license_count' => 12,
            'notes' => 'Can theo doi gia han hang nam.',
        ]);

        $response = $this->actingAs($manager)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee($customer->name);
        $response->assertSee($customer->phone);
        $response->assertSee($customer->email);
        $response->assertSee($customer->representative_name);
        $response->assertSee($customer->representative_phone);
        $response->assertSee((string) $customer->license_count);
        $response->assertSee($customer->address);
        $response->assertSee($customer->notes);
    }

    public function test_customer_requires_name_and_non_manager_cannot_access_management(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);
        $support = User::factory()->create(['is_manager' => false]);

        $this->actingAs($manager)
            ->from(route('customers.create'))
            ->post(route('customers.store'), [
                'name' => '',
            ])
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors(['name']);

        $this->actingAs($support)->get(route('customers.index'))->assertForbidden();
    }

    public function test_support_can_search_customers_for_ticket_creation_using_db_fallback(): void
    {
        config()->set('scout.driver', 'database');

        $support = User::factory()->create(['is_manager' => false]);
        Customer::query()->create([
            'name' => 'Cong ty Sao Bac',
            'representative_name' => 'Nguyen Ha',
            'phone' => '0909111222',
            'email' => 'hello@saobac.local',
            'license_count' => 15,
        ]);
        Customer::query()->create([
            'name' => 'Cong ty Bien Dong',
        ]);

        $response = $this->actingAs($support)->getJson(route('customers.search', [
            'q' => 'sao bac',
        ]));

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Cong ty Sao Bac',
            'contact_preview' => 'Nguyen Ha',
        ]);
    }

    public function test_manager_can_trigger_search_rebuild_from_admin_pages(): void
    {
        config()->set('scout.driver', 'meilisearch');
        $controller = Mockery::mock(SearchMaintenanceController::class, [
            Mockery::mock(EngineManager::class),
        ])->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('syncConfiguredIndexSettings')->once();
        $controller->shouldReceive('rebuildModelIndex')->with(Customer::class)->once();
        $controller->shouldReceive('rebuildModelIndex')->with(Ticket::class)->once();
        $this->app->instance(SearchMaintenanceController::class, $controller);

        $manager = User::factory()->create(['is_manager' => true]);

        $response = $this->actingAs($manager)->post(route('search.rebuild'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Đã rebuild lại index tìm kiếm.');
    }

    public function test_manager_can_trigger_search_rebuild_when_index_settings_command_is_unavailable(): void
    {
        config()->set('scout.driver', 'meilisearch');
        $controller = Mockery::mock(SearchMaintenanceController::class, [
            Mockery::mock(EngineManager::class),
        ])->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('syncConfiguredIndexSettings')->once();
        $controller->shouldReceive('rebuildModelIndex')->with(Customer::class)->once();
        $controller->shouldReceive('rebuildModelIndex')->with(Ticket::class)->once();
        $this->app->instance(SearchMaintenanceController::class, $controller);

        $manager = User::factory()->create(['is_manager' => true]);

        $response = $this->actingAs($manager)->post(route('search.rebuild'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Đã rebuild lại index tìm kiếm.');
    }
}
