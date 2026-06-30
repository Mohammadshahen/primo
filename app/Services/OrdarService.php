<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\DeliveryPrice;
use App\Models\Ordar;
use App\Models\OrdarItam;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class OrdarService extends Service
{
    public function confirmOrdar(User $user, array $data): array
    {
        try {
            $cartItems = Cart::where('user_id', $user->id)
                ->with(['variant.product'])
                ->get();

            if ($cartItems->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'السلة فارغة',
                    'status' => 400,
                ];
            }

            $isDelivere = (bool) ($data['is_delivere'] ?? false);
            $addressId = $data['address_id'] ?? null;
            $address = null;

            if ($isDelivere) {
                $address = Address::where('id', $addressId)
                    ->where('user_id', $user->id)
                    ->first();

                if (! $address) {
                    return [
                        'success' => false,
                        'message' => 'العنوان غير صالح للطلب',
                        'status' => 400,
                    ];
                }
            }

            $amount = 0.0;

            foreach ($cartItems as $cartItem) {
                $variant = $cartItem->variant;
                $product = $variant?->product;

                if (! $variant || ! $product || ! $variant->is_active || ! $product->is_active) {
                    return [
                        'success' => false,
                        'message' => 'يوجد عنصر في السلة غير متاح حالياً',
                        'status' => 400,
                    ];
                }

                if ($cartItem->count > $variant->stock) {
                    return [
                        'success' => false,
                        'message' => 'الكمية المطلوبة غير متوفرة في المخزون',
                        'status' => 400,
                    ];
                }

                $amount += $variant->price * $cartItem->count;
            }

            $deliveryAmount = $isDelivere ? DeliveryPrice::getDefaultPrice() : 0.0;
            $totalAmount = $amount + $deliveryAmount;

            DB::beginTransaction();

            $ordar = Ordar::create([
                'user_id' => $user->id,
                'address_id' => $address?->id,
                'status' => 'pending',
                'is_delivere' => $isDelivere,
                'amount' => $amount,
                'delivere_amount' => $deliveryAmount,
                'total_amount' => $totalAmount,
            ]);

            $items = [];
            foreach ($cartItems as $cartItem) {
                $items[] = [
                    'ordar_id' => $ordar->id,
                    'variant_id' => $cartItem->variant_id,
                    'count' => $cartItem->count,
                ];
            }

            OrdarItam::insert($items);
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'تم تأكيد الطلب بنجاح',
                'data' => $ordar->load(['items.variant.product', 'address']),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' confirmOrdar');

            return [
                'success' => false,
                'message' => 'فشل تأكيد الطلب',
                'status' => 500,
            ];
        }
    }
}
