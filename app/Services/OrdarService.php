<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Ordar;
use App\Models\OrdarItam;
use App\Models\Setting;
use App\Traits\DistanceTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Exceptions\HttpResponseException;

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
        if ($items->isEmpty()) {
            $this->throwExceptionJson('سلة التسوق فارغة', 400);
        }
        try {
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
        } catch (\Exception $e) {
            $this->logException($e, __METHOD__ . ' - Error calculating order price');

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حساب سعر الطلب',
            ];
        }
    }

    public function confirmeOrdar(array $data)
    {
        $orderPrice = $this->calculatePrice($data);
        $user_id = Auth::id();
        $items = Cart::with(['variant.product','variant'])->where('user_id', $user_id)->get();
        // return $items;
        try {
            DB::beginTransaction();
            $ordar = Ordar::create([
                'user_id' => $user_id,
                'address_id' => $data['address_id'] ?? null,
                'amount' => $orderPrice['item_price'],
                'delivere_amount' => $orderPrice['delivery_price'],
                'total_amount' => $orderPrice['total_price'],
            ]);
            foreach ($items as $item) {
                if( $item->variant->is_deliverable() == false){
                    // return $item->variant->is_deliverable;
                    $this->throwExceptionJson('المنتج ' . $item->variant->product->name . ' غير متاح للتوصيل', 400);
                }
                if( $item->variant->stock < $item->count){
                    $this->throwExceptionJson('الكمية المطلوبة من المنتج ' . $item->variant->product->name . ' غير متوفرة', 400);
                }
                OrdarItam::create([
                    'ordar_id' => $ordar->id,
                    'variant_id' => $item->variant_id,
                    'count' => $item->count,
                ]);

                $item->variant->decrement('stock', $item->count);
                $item->delete();
            };

            DB::commit();
            return $ordar;
        } catch (HttpResponseException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' - Error confirming order');
            $this->throwExceptionJson('حدث خطأ أثناء تأكيد الطلب', 500);
        }
    }
}
