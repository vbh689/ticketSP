<?php

namespace Tests\Feature\Admin;

use App\Models\Tag;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_tag_management_screen(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);
        TicketCategory::factory()->create(['name' => 'Phần mềm']);
        Tag::query()->create([
            'type' => Tag::TYPE_CONTACT_METHOD,
            'code' => 'telegram',
            'name' => 'Telegram',
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->get(route('admin.tags.index'));

        $response->assertOk();
        $response->assertSee('Quản lý tags');
        $response->assertSee('Loại ticket');
        $response->assertSee('Phương thức liên hệ');
        $response->assertSee('Phần mềm');
        $response->assertSee('Telegram');
    }

    public function test_manager_can_create_and_update_ticket_category_from_tag_admin_screen(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);

        $this->actingAs($manager)->post(route('ticket-categories.store'), [
            'name' => 'Remote Access',
        ])->assertRedirect(route('admin.tags.index'));

        $category = TicketCategory::query()->where('code', 'remote-access')->firstOrFail();

        $this->assertDatabaseHas('ticket_categories', [
            'id' => $category->id,
            'name' => 'Remote Access',
            'is_active' => true,
        ]);

        $this->actingAs($manager)->patch(route('ticket-categories.update', $category), [
            'name' => 'Remote Support',
        ])->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseHas('ticket_categories', [
            'id' => $category->id,
            'name' => 'Remote Support',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_create_and_update_contact_method_tags(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);

        $this->actingAs($manager)->post(route('tags.store'), [
            'type' => Tag::TYPE_CONTACT_METHOD,
            'name' => 'Teams',
        ])->assertRedirect(route('admin.tags.index'));

        $tag = Tag::query()
            ->where('type', Tag::TYPE_CONTACT_METHOD)
            ->where('code', 'teams')
            ->firstOrFail();

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Teams',
            'is_active' => true,
        ]);

        $this->actingAs($manager)->patch(route('tags.update', $tag), [
            'type' => Tag::TYPE_CONTACT_METHOD,
            'name' => 'Microsoft Teams',
        ])->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Microsoft Teams',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_delete_unused_ticket_category_and_contact_method_tag(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);
        $category = TicketCategory::factory()->create();
        $tag = Tag::query()->create([
            'type' => Tag::TYPE_CONTACT_METHOD,
            'code' => 'zalo',
            'name' => 'Zalo',
            'is_active' => true,
        ]);

        $this->actingAs($manager)->delete(route('ticket-categories.destroy', $category))
            ->assertRedirect(route('admin.tags.index'));
        $this->actingAs($manager)->delete(route('tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseMissing('ticket_categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_non_manager_cannot_access_tag_management_screen(): void
    {
        $support = User::factory()->create(['is_manager' => false]);

        $this->actingAs($support)->get(route('admin.tags.index'))->assertForbidden();
    }

    public function test_employee_form_uses_active_contact_method_tags_when_available(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);
        Tag::query()->create([
            'type' => Tag::TYPE_CONTACT_METHOD,
            'code' => 'email',
            'name' => 'Email',
            'is_active' => true,
        ]);
        Tag::query()->create([
            'type' => Tag::TYPE_CONTACT_METHOD,
            'code' => 'signal',
            'name' => 'Signal',
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->get(route('employees.create'));

        $response->assertOk();
        $response->assertSee('Signal');
    }
}
