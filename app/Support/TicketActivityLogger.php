<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;

class TicketActivityLogger
{
    public static function log(Ticket $ticket, ?User $actor, string $type, string $detail): TicketActivity
    {
        return $ticket->activities()->create([
            'actor_id' => $actor?->id,
            'action_type' => $type,
            'action_detail' => $detail,
        ]);
    }
}
