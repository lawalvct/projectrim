<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductMessageController extends Controller
{
    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'sender_name' => 'required|string|max:255',
            'sender_email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'honeypot' => 'present|max:0', // bot prevention
        ]);

        // Rate limiting: max 5 messages per IP per hour
        $ip = $request->ip();
        $recentCount = Message::where('created_at', '>=', now()->subHour())
            ->whereRaw("EXISTS (SELECT 1 FROM messages AS m WHERE m.id = messages.id)")
            ->count();

        // More accurate: check by sender_email + IP combination
        $recentCount = Message::where('sender_email', $request->input('sender_email'))
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentCount >= 5) {
            return response()->json([
                'message' => 'Too many messages. Please try again later.',
            ], 429);
        }

        // Create the message
        $message = Message::create([
            'product_id' => $product->id,
            'sender_name' => $request->input('sender_name'),
            'sender_email' => $request->input('sender_email'),
            'subject' => $request->input('subject'),
            'body' => $request->input('body'),
        ]);

        // Send to all authors (or product owner if no authors)
        $authors = $product->authors()->get();

        if ($authors->isEmpty()) {
            // Send to product owner
            MessageRecipient::create([
                'message_id' => $message->id,
                'user_id' => $product->user_id,
            ]);
        } else {
            // Send to all authors/co-authors
            foreach ($authors as $author) {
                MessageRecipient::create([
                    'message_id' => $message->id,
                    'user_id' => $author->user_id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Your message has been sent successfully.',
        ]);
    }
}
