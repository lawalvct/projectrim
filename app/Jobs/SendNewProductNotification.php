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

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $productId) {}

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(): void
    {
        $product = Product::with(['user', 'faculty'])->find($this->productId);

        if (! $product || $product->status !== 'published') {
            return;
        }

        $connection = (string) config('queue.notifications.connection', 'database');
        $queue = (string) config('queue.notifications.queue', 'default');

        User::where('id', '!=', $product->user_id)
            ->whereNotNull('email')
            ->select(['id', 'email'])
            ->chunkById(200, function ($users) use ($product, $connection, $queue) {
                foreach ($users as $user) {
                    $message = (new NewProductNotification($product))
                        ->onConnection($connection)
                        ->onQueue($queue);

                    Mail::to($user->email)->queue($message);
                }
            });
    }
}
