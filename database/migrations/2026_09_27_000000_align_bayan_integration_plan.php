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
            $table->dropForeign(['category_id']);
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->boolean('bayan_unavailable')->default(false);
            $table->decimal('price', 10, 3)->change();
        });

        DB::table('variants')
            ->select('bayan_id')
            ->whereNotNull('bayan_id')
            ->groupBy('bayan_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('bayan_id')
            ->each(function ($bayanId): void {
                DB::table('variants')->where('bayan_id', $bayanId)->update([
                    'bayan_id' => null,
                    'bayan_variant_key' => null,
                ]);
            });

        Schema::table('variants', function (Blueprint $table) {
            $table->dropIndex(['bayan_id']);
            $table->unique('bayan_id');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->text('note')->nullable();
        });

        Schema::table('ordar_itams', function (Blueprint $table) {
            $table->text('note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ordar_itams', function (Blueprint $table) {
            $table->dropColumn('note');
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('note');
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->dropUnique(['bayan_id']);
            $table->index('bayan_id');
            $table->dropColumn('bayan_unavailable');
            $table->decimal('price', 12, 3)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
