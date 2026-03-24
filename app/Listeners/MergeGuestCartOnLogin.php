<?php

namespace App\Listeners;

use App\Http\Controllers\CartController;
use Illuminate\Auth\Events\Login;

class MergeGuestCartOnLogin
{
    public function handle(Login $event): void
    {
        CartController::mergeGuestCart(request());
    }
}
