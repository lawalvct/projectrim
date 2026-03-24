<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // the author who earns
            $table->string('type'); // view, download, sale
            $table->decimal('amount_usd', 10, 4);
            $table->string('visitor_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['product_id', 'type']);
        });

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_usd', 10, 2);
            $table->string('status')->default('pending'); // pending, approved, paid, rejected
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->text('payment_details')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments_given', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_usd', 10, 2);
            $table->string('payment_method');
            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments_given');
        Schema::dropIfExists('payout_requests');
        Schema::dropIfExists('revenues');
    }
};
