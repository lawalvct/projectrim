<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_video_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('token')->unique();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('expected_size');
            $table->unsignedBigInteger('received_size')->default(0);
            $table->unsignedInteger('total_chunks');
            $table->json('uploaded_chunks')->nullable();
            $table->string('source_path')->nullable();
            $table->string('status', 20)->default('uploading');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_video_uploads');
    }
};
