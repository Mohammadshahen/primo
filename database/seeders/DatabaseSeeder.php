<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin',
            'phone' => '+963911113333',
            'phone_verified_at' => now(),
            'password' => bcrypt('12345678'),
            'is_admin' => true,
        ]);

        Address::create([
        'name'=> 'store_address',
        'user_id' => 1,
        'location_lat' => 33.5138,
        'location_lng' => 36.2765,
        'description' => 'nane',
        ]);

        Setting::setValue('delivery_price', 100.0);
        Setting::setValue('dollar_value', 1500.0);

        $this->call([
            CategoriesTableSeeder::class,
            ProductsTableSeeder::class,
            VariantsTableSeeder::class,
            OffersTableSeeder::class,
            AddressesTableSeeder::class,
            SuggestionSeeder::class,
        ]);
    }
}
