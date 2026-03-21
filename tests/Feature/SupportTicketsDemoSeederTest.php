<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketCategory;
use Database\Seeders\SupportTicketsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketsDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_tickets_demo_seeder_imports_csv_with_fixed_software_category(): void
    {
        $this->seed(SupportTicketsDemoSeeder::class);

        $softwareCategory = TicketCategory::query()->where('name', 'Phần mềm')->first();
        $ticket = Ticket::query()->where('ticket_code', 'TK-260223-001')->first();

        $this->assertNotNull($softwareCategory);
        $this->assertNotNull($ticket);
        $this->assertSame(425, Ticket::query()->count());
        $this->assertSame($softwareCategory->id, $ticket->category_id);
        $this->assertSame(Ticket::PRIORITY_NORMAL, $ticket->priority);
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->status);
        $this->assertSame('Zalo Pmbh', $ticket->requester_contact_method);
    }
}
