<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')
                ->constrained('variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->date('from');
            $table->date('to');
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};