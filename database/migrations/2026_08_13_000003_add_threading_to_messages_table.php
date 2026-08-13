<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New messages retain their authenticated sender; replies are separate
     * records attached to the original conversation. Legacy rows stay
     * unlinked because older versions accepted arbitrary sender emails.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('sender_user_id')->nullable()->after('product_id')->constrained('users')->nullOnDelete();
            $table->foreignId('parent_message_id')->nullable()->after('sender_user_id')->constrained('messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['parent_message_id']);
            $table->dropForeign(['sender_user_id']);
            $table->dropColumn(['parent_message_id', 'sender_user_id']);
        });
    }
};
