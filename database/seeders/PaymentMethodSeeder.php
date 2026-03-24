<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Stripe', 'is_active' => true],
            ['name' => 'PayPal', 'is_active' => true],
            ['name' => 'Paystack', 'is_active' => true],
            ['name' => 'Flutterwave', 'is_active' => true],
            ['name' => 'Bank Transfer', 'is_active' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(['name' => $method['name']], $method);
        }
    }
}
