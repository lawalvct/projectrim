<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('role')->default('user')->after('password'); // user, seller, admin
            $table->boolean('is_seller_approved')->default(false)->after('role');
            $table->string('provider')->nullable()->after('is_seller_approved'); // google, facebook, twitter
            $table->string('provider_id')->nullable()->after('provider');
        });

        // Make password nullable for social login users
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'role', 'is_seller_approved', 'provider', 'provider_id']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
