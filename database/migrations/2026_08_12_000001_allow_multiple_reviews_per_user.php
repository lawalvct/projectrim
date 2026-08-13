<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(
                ['product_id', 'user_id', 'created_at'],
                'reviews_product_user_created_at_index'
            );
        });

        // MySQL may use the old unique index to support the product foreign
        // key, so its non-unique replacement must exist before it is dropped.
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_product_id_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['product_id', 'user_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_product_user_created_at_index');
        });
    }
};
