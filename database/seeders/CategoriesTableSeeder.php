<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'الكترونيات'],
            ['name' => 'أثاث'],
            ['name' => 'ملابس'],
            ['name' => 'مطبخ'],
            ['name' => 'كتب'],
        ];

        foreach ($categories as $category) {
            Categorie::create($category);
        }
    }
}
