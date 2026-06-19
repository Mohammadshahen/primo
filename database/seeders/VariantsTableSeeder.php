<?php

namespace Database\Seeders;

use App\Models\Variant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VariantsTableSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $variants = [
            [
                'product_id' => 1,
                'price' => 1500.00,
                'stock' => 10,
                'property' => 'اللون أسود',
                'is_active' => true,
            ],
            [
                'product_id' => 1,
                'price' => 1600.00,
                'stock' => 5,
                'property' => 'اللون أبيض',
                'is_active' => true,
            ],
        ];

        foreach ($variants as $variant) {
            Variant::create($variant);
        }
    }
}
