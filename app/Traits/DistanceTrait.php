<?php

namespace App\Traits;

use App\Models\Address;

trait DistanceTrait
{
    public function calculateDistance($id): ?float
    {
        $address = Address::where('id', $id)->first();
        $store_address = Address::where('name', 'store_address')->first();

        $latFrom = $address->location_lat;
        $lonFrom = $address->location_lng;
        $latTo = $store_address->location_lat;
        $lonTo = $store_address->location_lng;

        if ($latFrom === null || $lonFrom === null || $latTo === null || $lonTo === null) {
            return (float) 0; // Return 0 if any of the coordinates are missing
        }

        $earthRadiusKm = 6371;

        $latFromRad = deg2rad($latFrom);
        $lonFromRad = deg2rad($lonFrom);
        $latToRad = deg2rad($latTo);
        $lonToRad = deg2rad($lonTo);

        $latDelta = $latToRad - $latFromRad;
        $lonDelta = $lonToRad - $lonFromRad;

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos($latFromRad) * cos($latToRad)
            * sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c * 1000, 2);
    }
}
