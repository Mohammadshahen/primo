<?php

namespace Database\Seeders;

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

        $this->call([
            CategoriesTableSeeder::class,
            ProductsTableSeeder::class,
            VariantsTableSeeder::class,
            OffersTableSeeder::class,
            AddressesTableSeeder::class,
        ]);
    }
}
