<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('preview_video_source_path')->nullable()->after('preview_video');
            $table->uuid('preview_video_processing_token')->nullable()->after('preview_video_source_path');
            $table->string('preview_video_status', 20)->default('none')->after('preview_video_source_path');
            $table->text('preview_video_error')->nullable()->after('preview_video_status');
            $table->timestamp('preview_video_processed_at')->nullable()->after('preview_video_error');
            $table->index('preview_video_status');
            $table->index('preview_video_processing_token');
        });

        DB::table('products')
            ->whereNotNull('preview_video')
            ->update(['preview_video_status' => 'ready']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['preview_video_status']);
            $table->dropIndex(['preview_video_processing_token']);
            $table->dropColumn(['preview_video_source_path', 'preview_video_processing_token', 'preview_video_status', 'preview_video_error', 'preview_video_processed_at']);
        });
    }
};
