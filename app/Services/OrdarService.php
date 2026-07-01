<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Setting;
use App\Traits\DistanceTrait;
use Illuminate\Support\Facades\Auth;

class OrdarService extends Service
{
    use DistanceTrait;
    public function calculatePrice(array $data)
    {
        $user_id = Auth::id();
        if ($data['is_delivery'] ?? false) {
            $address = Address::where('id', $data['address_id'] ?? null)->first();
            if ($user_id != $address?->user_id) {
                $this->throwExceptionJson('العنوان المحدد لا ينتمي للمستخدم الحالي', 403);
            }
        }
        $items = Cart::with('variant.activeoffer')->where('user_id', $user_id)->get();
        $result['item_price'] = 0;
        foreach ($items as $item) {
            $discount = $item->variant?->activeOffer->discount_value ?? 0;
            $price_old = ($item->variant->price - $discount);
            $price = $price_old * $item->count;
            $result['item_price'] += $price;
        }

        $result['distance'] = 0;
        $result['delivery_price'] = 0;
        $result['delivery_fee_for_meter'] = Setting::getValue('delivery_price', 0);


        if ($data['is_delivery'] ?? false) {
            $result['distance'] = $this->calculateDistance($data['address_id'] ?? null);
            $result['delivery_price'] = $result['distance'] * $result['delivery_fee_for_meter'];
            $result['delivery_price'] = round($result['delivery_price']);
        }

        $result['total_price'] = $result['item_price'] + $result['delivery_price'];

        return [
            'item_price' => $result['item_price'],
            // 'distance' => $result['distance'],
            'delivery_price' => $result['delivery_price'],
            // 'delivery_fee_for_meter' => $result['delivery_fee_for_meter'],
            'total_price' => $result['total_price'],
        ];
    }
}
