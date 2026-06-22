<?php

namespace Database\Seeders;

use App\Models\Offer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OffersTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offers = [
            [
                'variant_id' => 1,
                'from' => now()->subDay(),
                'to' => now()->addDays(15),
                'discount_percentage' => 15,
                'discount_value' => null,
            ],
            [
                'variant_id' => 2,
                'from' => now()->addDay(),
                'to' => now()->addDays(20),
                'discount_percentage' => null,
                'discount_value' => 200,
            ],
        ];

        foreach ($offers as $offer) {
            Offer::updateOrCreate(
                ['variant_id' => $offer['variant_id']],
                $offer
            );
        }
    }
}
