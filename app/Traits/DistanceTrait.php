<?php

namespace App\Traits;

use App\Models\Address;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use TeamPickr\DistanceMatrix\DistanceMatrix;
use TeamPickr\DistanceMatrix\Licenses\StandardLicense;

use function Illuminate\Log\log;

trait DistanceTrait
{
    // public function calculateDistance($id): ?float
    // {
    //     $address = Address::where('id', $id)->first();
    //     $store_address = Address::where('name', 'store_address')->first();

    //     $latFrom = $address->location_lat;
    //     $lonFrom = $address->location_lng;
    //     $latTo = $store_address->location_lat;
    //     $lonTo = $store_address->location_lng;

    //     if ($latFrom === null || $lonFrom === null || $latTo === null || $lonTo === null) {
    //         return (float) 0; // Return 0 if any of the coordinates are missing
    //     }

    //     $earthRadiusKm = 6371;

    //     $latFromRad = deg2rad($latFrom);
    //     $lonFromRad = deg2rad($lonFrom);
    //     $latToRad = deg2rad($latTo);
    //     $lonToRad = deg2rad($lonTo);

    //     $latDelta = $latToRad - $latFromRad;
    //     $lonDelta = $lonToRad - $lonFromRad;

    //     $a = sin($latDelta / 2) * sin($latDelta / 2)
    //         + cos($latFromRad) * cos($latToRad)
    //         * sin($lonDelta / 2) * sin($lonDelta / 2);

    //     $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    //     return round($earthRadiusKm * $c * 1000, 2);
    // }


    public function calculateDistance($id): float
    {
        $address = Address::find($id);
        $store_address = Address::where('name', 'store_address')->first();

        if (! $address || ! $store_address) {
            return 0.0;
        }

        $latFrom = $address->location_lat;
        $lonFrom = $address->location_lng;
        $latTo = $store_address->location_lat;
        $lonTo = $store_address->location_lng;

        if ($latFrom === null || $lonFrom === null || $latTo === null || $lonTo === null) {
            throw new HttpResponseException(response()->json(['success' => false,
            'message' => 'العنوان المدخل غير صحيح'], 422));
        }

        $origin = sprintf('%s,%s', $latFrom, $lonFrom);
        $destination = sprintf('%s,%s', $latTo, $lonTo);

        $license = new StandardLicense(env('GOOGLE_MAPS_KEY'));

        $response = DistanceMatrix::license($license)
            ->addOrigin($origin)
            ->addDestination($destination)
            ->request();

        Log::write('info', 'Distance Matrix Response: ' . json_encode($response));

        if (! $response->successful()) {
            throw new HttpResponseException(response()->json(['success' => false,
            'message' => 'حدث خطأ ما'], 500));
        }

        $row = $response->row();

        if (! $row) {
            throw new HttpResponseException(response()->json(['success' => false,
            'message' => 'حدث خطأ ما'], 500));
        }

        $element = $row->element();

        if (! $element || ! $element->successful()) {
            throw new HttpResponseException(response()->json(['success' => false,
            'message' => 'حدث خطأ ما'], 500));
        }

        $distanceInMeters = $element->distance();

        return $distanceInMeters !== null ? (float) $distanceInMeters : 0.0;
    }
}

