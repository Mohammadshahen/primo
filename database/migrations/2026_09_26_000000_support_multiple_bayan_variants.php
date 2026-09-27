<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->char('bayan_variant_key', 64)->nullable()->unique();
        });

        DB::table('variants')
            ->select([
                'variants.id as variant_row_id',
                'variants.bayan_id',
                'variants.property',
            ])
            ->whereNotNull('variants.bayan_id')
            ->orderBy('variants.id')
            ->chunk(500, function ($variants): void {
                foreach ($variants as $variant) {
                    $identity = trim((string) $variant->property);
                    if ($identity === '' || $identity === 'الافتراضي') {
                        $identity = '__default__';
                    }

                    DB::table('variants')
                        ->where('id', $variant->variant_row_id)
                        ->update([
                            'bayan_variant_key' => hash('sha256', $variant->bayan_id."\0".$identity),
                        ]);
                }
            });

        Schema::table('variants', function (Blueprint $table) {
            $table->dropUnique(['bayan_id']);
            $table->index('bayan_id');
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropIndex(['bayan_id']);
            $table->unique('bayan_id');
            $table->dropUnique(['bayan_variant_key']);
            $table->dropColumn('bayan_variant_key');
        });
    }
};
