<?php

namespace App\Jobs;

use App\Mail\NewProductNotification;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendNewProductNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Product $product) {}

    public function handle(): void
    {
        $this->product->load(['user', 'faculty']);

        User::where('id', '!=', $this->product->user_id)
            ->select('email')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    Mail::to($user->email)->queue(new NewProductNotification($this->product));
                }
            });
    }
}
