<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('bayan_id')->nullable()->unique();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('bayan_id')->nullable()->unique();
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->unsignedBigInteger('bayan_id')->nullable()->unique();
            $table->unsignedInteger('bayan_currency_id')->nullable();
            $table->decimal('price', 12, 3)->change();
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropUnique(['bayan_id']);
            $table->dropColumn(['bayan_id', 'bayan_currency_id']);
            $table->decimal('price', 12, 2)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['bayan_id']);
            $table->dropColumn('bayan_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['bayan_id']);
            $table->dropColumn('bayan_id');
        });
    }
};
