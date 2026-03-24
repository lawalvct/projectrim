<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MessageRecipient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MessageRecipient::with([
            'message:id,product_id,sender_name,sender_email,subject,body,created_at',
            'message.product:id,title,slug',
        ])
            ->where('user_id', $request->user()->id);

        if ($request->filter === 'unread') {
            $query->where('is_read', false);
        }

        $messages = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($recipient) => [
                'id' => $recipient->id,
                'message_id' => $recipient->message_id,
                'is_read' => (bool) $recipient->is_read,
                'message' => $recipient->message ? [
                    'id' => $recipient->message->id,
                    'sender_name' => $recipient->message->sender_name,
                    'sender_email' => $recipient->message->sender_email,
                    'subject' => $recipient->message->subject,
                    'body' => $recipient->message->body,
                    'created_at' => $recipient->message->created_at->format('M d, Y'),
                    'created_at_diff' => $recipient->message->created_at->diffForHumans(),
                    'product' => $recipient->message->product ? [
                        'id' => $recipient->message->product->id,
                        'title' => $recipient->message->product->title,
                        'slug' => $recipient->message->product->slug,
                    ] : null,
                ] : null,
            ]);

        $unreadCount = MessageRecipient::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return Inertia::render('dashboard/Messages', [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
            'filters' => [
                'filter' => $request->filter,
            ],
        ]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $recipient = MessageRecipient::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $recipient->update([
            'is_read' => true,
        ]);

        return back();
    }

    public function markAllAsRead(Request $request)
    {
        MessageRecipient::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
            ]);

        return back();
    }
}
