<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Revenue;
use App\Models\Setting;

class RevenueService
{
    /**
     * Record view revenue for a product, distributed among authors.
     * Anti-fraud: max 1 view reward per IP per product per 24 hours.
     */
    public function recordViewRevenue(Product $product, string $ip): void
    {
        // Anti-fraud: check if this IP already earned view revenue for this product in last 24h
        $exists = Revenue::where('product_id', $product->id)
            ->where('type', 'view')
            ->where('visitor_ip', $ip)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($exists) {
            return;
        }

        $amount = (float) Setting::getValue('view_reward_usd', '0.10');

        if ($amount <= 0) {
            return;
        }

        $this->distributeRevenue($product, 'view', $amount, $ip);
    }

    /**
     * Record download revenue for a product, distributed among authors.
     * Anti-fraud: max 1 download reward per IP per product per 24 hours.
     */
    public function recordDownloadRevenue(Product $product, ?int $userId, string $ip): void
    {
        $exists = Revenue::where('product_id', $product->id)
            ->where('type', 'download')
            ->where('visitor_ip', $ip)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($exists) {
            return;
        }

        $amount = (float) Setting::getValue('download_reward_usd', '1.00');

        if ($amount <= 0) {
            return;
        }

        $this->distributeRevenue($product, 'download', $amount, $ip);
    }

    /**
     * Distribute revenue among product authors based on contribution percentage.
     */
    private function distributeRevenue(Product $product, string $type, float $amount, ?string $ip): void
    {
        $authors = $product->authors()->with('user')->get();

        if ($authors->isEmpty()) {
            // Fallback: all revenue to product owner
            Revenue::create([
                'product_id' => $product->id,
                'user_id' => $product->user_id,
                'type' => $type,
                'amount_usd' => round($amount, 4),
                'visitor_ip' => $ip,
            ]);
        } else {
            foreach ($authors as $author) {
                $share = ($author->contribution_percentage / 100) * $amount;
                Revenue::create([
                    'product_id' => $product->id,
                    'user_id' => $author->user_id,
                    'type' => $type,
                    'amount_usd' => round($share, 4),
                    'visitor_ip' => $ip,
                ]);
            }
        }
    }
}
