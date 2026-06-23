<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;

class AddressesTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_admin', false)->get();

        if ($users->isEmpty()) {
            return;
        }

        $defaultAddresses = [
            [
                'name' => 'المنزل',
                'location_lat' => '33.510414',
                'location_lng' => '36.278336',
                'description' => 'العنوان الرئيسي',
            ],
            [
                'name' => 'العمل',
                'location_lat' => '33.513000',
                'location_lng' => '36.280000',
                'description' => 'عنوان العمل',
            ],
            [
                'name' => 'الاستراحة',
                'location_lat' => '33.515000',
                'location_lng' => '36.282000',
                'description' => 'عنوان إضافي',
            ],
        ];

        foreach ($users as $user) {
            foreach ($defaultAddresses as $addressData) {
                Address::firstOrCreate([
                    'user_id' => $user->id,
                    'name' => $addressData['name'],
                ], $addressData);
            }
        }
    }
}
