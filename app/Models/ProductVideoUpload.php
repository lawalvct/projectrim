<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVideoUpload extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expected_size' => 'integer', 'received_size' => 'integer', 'total_chunks' => 'integer', 'uploaded_chunks' => 'array', 'completed_at' => 'datetime', 'claimed_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
