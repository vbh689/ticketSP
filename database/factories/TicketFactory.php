<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'requester_name' => fake()->name(),
            'requester_contact' => fake()->safeEmail(),
            'requester_contact_method' => fake()->randomElement(['Email', 'Phone', 'Telegram']),
            'priority' => fake()->randomElement(\App\Models\Ticket::priorities()),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category_id' => TicketCategory::factory(),
            'status' => Ticket::STATUS_OPEN,
            'assignee_id' => null,
            'created_by' => User::factory(),
        ];
    }
}
