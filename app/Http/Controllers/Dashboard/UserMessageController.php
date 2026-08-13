<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\AuthorReplyNotification;
use App\Models\Message;
use App\Models\MessageRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UserMessageController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MessageRecipient::with([
            'message:id,product_id,sender_user_id,parent_message_id,sender_name,sender_email,subject,body,created_at',
            'message.product:id,user_id,title,slug',
            'message.product.authors:id,product_id,user_id',
            'message.replies:id,product_id,sender_user_id,parent_message_id,sender_name,sender_email,subject,body,created_at',
            'message.parent:id,product_id,sender_user_id,parent_message_id,sender_name,sender_email,subject,body,created_at',
            'message.parent.product:id,user_id,title,slug',
            'message.parent.product.authors:id,product_id,user_id',
            'message.parent.replies:id,product_id,sender_user_id,parent_message_id,sender_name,sender_email,subject,body,created_at',
        ])
            ->where('user_id', $request->user()->id);

        if ($request->filter === 'unread') {
            $query->where('is_read', false);
        }

        $messages = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(function (MessageRecipient $recipient) use ($request): array {
                $message = $recipient->message;

                return [
                    'id' => $recipient->id,
                    'message_id' => $recipient->message_id,
                    'is_read' => (bool) $recipient->is_read,
                    'can_reply' => $message !== null
                        && $this->canReplyTo($message, $request->user()->id),
                    'thread' => $message ? $this->threadPayload($message) : null,
                ];
            });

        $unreadCount = MessageRecipient::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return Inertia::render('dashboard/Messages', [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
            'currentUserId' => $request->user()->id,
            'filters' => [
                'filter' => $request->filter,
            ],
        ]);
    }

    public function reply(Request $request, int $recipient): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);
        $author = $request->user();

        [$reply, $sender, $originalMessage] = DB::transaction(function () use ($author, $recipient, $validated): array {
            $messageRecipient = MessageRecipient::query()
                ->with(['message.product.authors', 'message.sender'])
                ->where('user_id', $author->id)
                ->lockForUpdate()
                ->findOrFail($recipient);
            $originalMessage = $messageRecipient->message;

            abort_if($originalMessage === null || $originalMessage->parent_message_id !== null, 403);

            $authorIds = $originalMessage->product->authors
                ->pluck('user_id')
                ->whenEmpty(fn (Collection $ids) => $ids->push($originalMessage->product->user_id))
                ->unique();

            abort_unless($authorIds->contains($author->id), 403);

            $sender = $originalMessage->sender;

            if ($sender === null) {
                throw ValidationException::withMessages([
                    'body' => 'The original sender no longer has an account that can receive a dashboard reply.',
                ]);
            }

            if ($sender->is($author)) {
                throw ValidationException::withMessages([
                    'body' => 'You cannot reply to your own message.',
                ]);
            }

            $recentReplies = Message::query()
                ->where('sender_user_id', $author->id)
                ->where('parent_message_id', $originalMessage->id)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($recentReplies >= 10) {
                throw ValidationException::withMessages([
                    'body' => 'Too many replies to this conversation. Please try again later.',
                ]);
            }

            $reply = Message::create([
                'product_id' => $originalMessage->product_id,
                'sender_user_id' => $author->id,
                'parent_message_id' => $originalMessage->id,
                'sender_name' => $author->name,
                'sender_email' => $author->email,
                'subject' => $originalMessage->subject,
                'body' => $validated['body'],
            ]);

            MessageRecipient::updateOrCreate([
                'message_id' => $originalMessage->id,
                'user_id' => $sender->id,
            ], [
                'is_read' => false,
            ]);

            return [$reply->load('product'), $sender, $originalMessage->load('product')];
        });

        // The reply is already committed. Queueing email must never turn a
        // successful dashboard reply into an HTTP 500 response.
        try {
            $notification = (new AuthorReplyNotification($reply, $sender, $originalMessage))
                ->onConnection((string) config('queue.notifications.connection', 'database'))
                ->onQueue((string) config('queue.notifications.queue', 'default'));

            Mail::to($sender->email)->queue($notification);
        } catch (Throwable $exception) {
            Log::warning('Message reply saved, but its email notification could not be queued.', [
                'reply_id' => $reply->id,
                'recipient_id' => $sender->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Reply sent successfully.',
            'reply' => $this->messageEntry($reply, $originalMessage->id),
        ], 201);
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

    private function threadPayload(Message $message): array
    {
        $root = $message->parent ?? $message;
        $entries = collect([$root])
            ->merge($root->replies)
            ->sortBy('created_at')
            ->values()
            ->map(fn (Message $entry): array => $this->messageEntry($entry, $root->id))
            ->all();

        return [
            'id' => $root->id,
            'subject' => $root->subject,
            'product' => $root->product ? [
                'id' => $root->product->id,
                'title' => $root->product->title,
                'slug' => $root->product->slug,
            ] : null,
            'entries' => $entries,
        ];
    }

    private function canReplyTo(Message $message, int $userId): bool
    {
        if (
            $message->parent_message_id !== null
            || $message->sender_user_id === null
            || $message->sender_user_id === $userId
            || $message->product === null
        ) {
            return false;
        }

        $authorIds = $message->product->authors
            ->pluck('user_id')
            ->whenEmpty(fn (Collection $ids) => $ids->push($message->product->user_id));

        return $authorIds->contains($userId);
    }

    private function messageEntry(Message $message, int $rootId): array
    {
        return [
            'id' => $message->id,
            'sender_user_id' => $message->sender_user_id,
            'sender_name' => $message->sender_name,
            'sender_email' => $message->sender_email,
            'body' => $message->body,
            'created_at' => $message->created_at->format('M d, Y g:i A'),
            'created_at_diff' => $message->created_at->diffForHumans(),
            'is_original' => $message->id === $rootId,
        ];
    }
}
