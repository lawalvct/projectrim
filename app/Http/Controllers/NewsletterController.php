<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $subscriber = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($subscriber) {
            if ($subscriber->is_active) {
                return response()->json(['message' => 'You are already subscribed.'], 200);
            }

            $subscriber->update(['is_active' => true, 'unsubscribed_at' => null]);

            return response()->json(['message' => 'Welcome back! You have been re-subscribed.']);
        }

        NewsletterSubscriber::create([
            'email' => $validated['email'],
            'name' => $validated['name'] ?? null,
            'is_active' => true,
            'subscribed_at' => now(),
        ]);

        return response()->json(['message' => 'Thank you for subscribing!']);
    }
}
