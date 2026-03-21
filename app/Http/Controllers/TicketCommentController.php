<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Support\TicketActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ], [], [
            'content' => 'nội dung ghi chú',
        ]);

        $ticket->comments()->create([
            'author_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        TicketActivityLogger::log(
            $ticket->fresh(),
            $request->user(),
            'comment_added',
            'Thêm ghi chú xử lý mới.'
        );

        return back()->with('status', 'Đã thêm ghi chú xử lý.');
    }
}
