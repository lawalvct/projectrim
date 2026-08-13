<?php

namespace App\Http\Controllers;

use App\Mail\NewMessageNotification;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProductMessageController extends Controller
{
    public function store(Request $request, Product $product): JsonResponse
    {
        if (! $request->user()) {
            $request->session()->put('url.intended', route('products.show', $product->slug));
            $request->session()->flash('message', 'Please log in to send a message.');

            return response()->json([
                'redirect' => route('login'),
                'message' => 'Please log in to send a message.',
            ], 401);
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'honeypot' => 'present|max:0', // bot prevention
        ]);

        $sender = $request->user();

        // Rate limiting: max 5 messages per sender per hour.
        $recentCount = Message::where('sender_user_id', $sender->id)
            ->whereNull('parent_message_id')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentCount >= 5) {
            return response()->json([
                'message' => 'Too many messages. Please try again later.',
            ], 429);
        }

        [$message, $recipientUserIds] = DB::transaction(function () use ($sender, $validated, $product) {
            $message = Message::create([
                'product_id' => $product->id,
                'sender_user_id' => $sender->id,
                'sender_name' => $sender->name,
                'sender_email' => $sender->email,
                'subject' => $validated['subject'],
                'body' => $validated['body'],
            ]);

            // Send to every distinct author, or the product owner when the
            // product has no author records.
            $recipientUserIds = $product->authors()
                ->pluck('user_id')
                ->whenEmpty(fn ($ids) => $ids->push($product->user_id))
                ->unique()
                ->values();

            foreach ($recipientUserIds as $recipientUserId) {
                MessageRecipient::create([
                    'message_id' => $message->id,
                    'user_id' => $recipientUserId,
                ]);
            }

            return [$message, $recipientUserIds];
        });

        // The inbox message is already committed. Email is only a background
        // notification and must never turn a successful send into HTTP 500.
        $connection = (string) config('queue.notifications.connection', 'database');
        $queue = (string) config('queue.notifications.queue', 'default');
        $recipients = User::whereIn('id', $recipientUserIds)
            ->whereNotNull('email')
            ->get();

        foreach ($recipients as $recipient) {
            try {
                $notification = (new NewMessageNotification($message, $recipient))
                    ->onConnection($connection)
                    ->onQueue($queue);

                Mail::to($recipient->email)->queue($notification);
            } catch (\Throwable $exception) {
                Log::warning('Message saved, but its email notification could not be queued.', [
                    'message_id' => $message->id,
                    'recipient_id' => $recipient->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Your message has been sent successfully.',
            'message_id' => $message->id,
        ]);
    }
}
