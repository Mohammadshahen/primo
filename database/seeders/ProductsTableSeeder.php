<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'category_id' => 1,
                'name' => 'هاتف ذكي',
                'description' => 'هاتف ذكي حديث مع كاميرا عالية الجودة.',
                'sku_code' => 'PRD-SMART-001',
            ],
            [
                'category_id' => 2,
                'name' => 'أريكة مريحة',
                'description' => 'أريكة مع تصميم عصري وجودة عالية.',
                'sku_code' => 'PRD-FURN-002',
            ],
            [
                'category_id' => 3,
                'name' => 'قميص قطني',
                'description' => 'قميص قطن ناعم ومناسب لجميع الأوقات.',
                'sku_code' => 'PRD-CLTH-003',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
