<?php

namespace Tests\Feature\Employees;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_can_create_and_update_employee_profile(): void
    {
        $support = User::factory()->create();

        $this->actingAs($support)->post(route('employees.store'), [
            'email' => 'new.agent@internal.local',
            'username' => 'new.agent',
            'name' => 'Nguyen New Agent',
            'phone' => '0909888777',
            'department' => 'IT Support',
            'primary_contact_method' => 'Telegram',
            'status' => 'active',
            'password' => 'password123',
        ])->assertRedirect(route('employees.index'));

        $employee = User::query()->where('email', 'new.agent@internal.local')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'username' => 'new.agent',
            'department' => 'IT Support',
            'primary_contact_method' => 'Telegram',
        ]);

        $this->actingAs($support)->patch(route('employees.update', $employee), [
            'email' => 'new.agent@internal.local',
            'username' => 'new.agent',
            'name' => 'Nguyen Updated Agent',
            'phone' => '0909777666',
            'department' => 'Infrastructure',
            'primary_contact_method' => 'Phone',
            'status' => 'inactive',
            'password' => '',
        ])->assertRedirect(route('employees.index'));

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'Nguyen Updated Agent',
            'phone' => '0909777666',
            'department' => 'Infrastructure',
            'primary_contact_method' => 'Phone',
            'status' => 'inactive',
        ]);
    }

    public function test_employee_index_displays_extended_profile_fields(): void
    {
        $support = User::factory()->create();
        $employee = User::factory()->create([
            'email' => 'viewer.agent@internal.local',
            'username' => 'viewer.agent',
            'name' => 'Tran Viewer',
            'phone' => '0911222333',
            'department' => 'Helpdesk',
            'primary_contact_method' => 'Telegram',
        ]);

        $response = $this->actingAs($support)->get(route('employees.index'));

        $response->assertOk();
        $response->assertSee($employee->email);
        $response->assertSee($employee->username);
        $response->assertSee($employee->name);
        $response->assertSee($employee->phone);
        $response->assertSee($employee->department);
        $response->assertSee($employee->primary_contact_method);
    }
}
