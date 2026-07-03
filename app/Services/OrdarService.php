<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Ordar;
use App\Models\OrdarItam;
use App\Models\Product;
use App\Models\Setting;
use App\Traits\DistanceTrait;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $items = Cart::with(['variant'])->where('user_id', $user_id)->get();
        // return $items;
        try {
            DB::beginTransaction();
            $ordar = Ordar::create([
                'user_id' => $user_id,
                'address_id' => $data['address_id'] ?? null,
                'is_delivere' => $data['is_delivery'] ?? false,
                'amount' => $orderPrice['item_price'],
                'delivere_amount' => $orderPrice['delivery_price'],
                'total_amount' => $orderPrice['total_price'],
            ]);
            foreach ($items as $item) {
                // return ['is'=>$item->variant->is_deliverable()];
                if ($item->variant->is_available() == false) {
                    $this->throwExceptionJson('المنتج ' . $item->variant->product->name . ' غير متاح ', 400);
                }
                if ($item->variant->stock < $item->count) {
                    $this->throwExceptionJson('الكمية المطلوبة من المنتج ' . $item->variant->product->name . ' غير متوفرة', 400);
                }
                OrdarItam::create([
                    'ordar_id' => $ordar->id,
                    'variant_id' => $item->variant_id,
                    'count' => $item->count,
                ]);

                $item->variant->decrement('stock', $item->count);
                if ($item->variant->stock == 0) {
                    $item->variant->update(['is_active' => false]);
                }
                $this->checkProductAvailability($item->variant->product_id);
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

    
    public function checkProductAvailability($product_id)
    {

        $product = Product::with(['variants' => function ($query) {
            $query->where('is_active', true);
        }])->find($product_id);

        if ($product->variants->isEmpty()) {
            $product->update(['is_active' => false]);
        }
    }
    



    public function getAllOrdars(array $filters)
    {
        try {
            $query = Ordar::with(['user:id,name,phone,avatar']);
    
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
    
            return $query->orderByDesc('created_at')->get();
        } catch (\Exception $e) {
            $this->logException($e, __METHOD__ . ' - Error fetching orders');
            $this->throwExceptionJson('حدث خطأ أثناء جلب الطلبات', 500);
        }
    }
    
    public function getSingleOrdar(Ordar $ordar)
    {
        try {
            $ordar = $ordar->load([
                'user:id,name,phone,avatar,avatar',
                'address',
                'items.variant.product',
            ]);

             $ordar->items->transform(function($item){
                $variant= $item->variant;
                $product = $item->variant->product;
                return [
                    'name' => $product->name,
                    'image' => $product->image,
                    'quantity' => $item->count,
                    'property' => $variant->property,
                    'price' => $variant->price,
                    'has_active_offer' => $variant->has_active_offer,
                    'new_price' => $variant->has_active_offer ? $variant->price - $variant->offer->discount_value : null,

                    ];

            });

            return $ordar;

             
        } catch (\Exception $e) {
            $this->logException($e, __METHOD__ . ' - Error fetching order details');
            $this->throwExceptionJson('حدث خطأ أثناء جلب تفاصيل الطلب', 500);
        }
    }


    public function changeOrdarStatus(array $data,Ordar $ordar)
    {
        try {
            $ordar->update(['status' => $data['status']]);
            return $ordar;
        } catch (\Exception $e) {
            $this->logException($e, __METHOD__ . ' - Error changing order status');
            $this->throwExceptionJson('حدث خطأ أثناء تغيير حالة الطلب', 500);
        }
    }
}
