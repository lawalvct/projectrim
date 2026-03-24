<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('bio')->nullable();
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('region_state')->nullable();
            $table->foreignId('preferred_payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->text('bank_account_details')->nullable();
            $table->string('company_logo')->nullable();
            $table->string('banner')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
