<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'preferred_payment_method_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferredPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'preferred_payment_method_id');
    }
}
