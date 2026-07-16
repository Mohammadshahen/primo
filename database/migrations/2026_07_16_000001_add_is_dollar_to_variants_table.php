<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            if (! Schema::hasColumn('variants', 'is_dollar')) {
                $table->boolean('is_dollar')->default(false)->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            if (Schema::hasColumn('variants', 'is_dollar')) {
                $table->dropColumn('is_dollar');
            }
        });
    }
};
