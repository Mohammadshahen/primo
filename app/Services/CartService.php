<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use App\Models\Variant;
use Exception;
use Illuminate\Support\Facades\DB;

class CartService extends Service
{
    public function listItems(int $userId): array
    {
        try {
            $items = Cart::where('user_id', $userId)
                ->with(['variant.product'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $items->map(function (Cart $item) {
                return $this->formatCartItem($item);
            })->values();

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' listItems');

            return [
                'success' => false,
                'message' => 'فشل جلب السلة',
                'status' => 500,
            ];
        }
    }

    public function addItem(User $user, array $data): array
    {
        try {
            $variant = Variant::find($data['variant_id'] ?? null);

            if (! $variant) {
                return [
                    'success' => false,
                    'message' => 'النوع غير موجود',
                    'status' => 400,
                ];
            }

            if (! $variant->is_active || ! $variant->product || ! $variant->product->is_active) {
                return [
                    'success' => false,
                    'message' => 'هذا النوع غير متاح حالياً',
                    'status' => 400,
                ];
            }

            $count = max(1, (int) ($data['count'] ?? 1));

            if ($count > $variant->stock) {
                return [
                    'success' => false,
                    'message' => 'الكمية المطلوبة غير متوفرة',
                    'status' => 400,
                ];
            }

            DB::beginTransaction();

            $cartItem = Cart::where('user_id', $user->id)
                ->where('variant_id', $variant->id)
                ->first();

            if ($cartItem) {
                $newCount = $cartItem->count + $count;

                if ($newCount > $variant->stock) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'الكمية المطلوبة غير متوفرة',
                        'status' => 400,
                    ];
                }

                $cartItem->update(['count' => $newCount]);
            } else {
                $cartItem = Cart::create([
                    'user_id' => $user->id,
                    'variant_id' => $variant->id,
                    'count' => $count,
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'تمت إضافة العنصر إلى السلة بنجاح',
                'data' => $this->formatCartItem($cartItem->fresh(['variant.product'])),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' addItem');

            return [
                'success' => false,
                'message' => 'فشل إضافة العنصر إلى السلة',
                'status' => 500,
            ];
        }
    }

    public function updateItem(User $user, Cart $cart, array $data): array
    {
        try {
            if ($cart->user_id !== $user->id) {
                return [
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا العنصر',
                    'status' => 403,
                ];
            }

            $variant = $cart->variant;

            if (! $variant || ! $variant->is_active || ! $variant->product || ! $variant->product->is_active) {
                return [
                    'success' => false,
                    'message' => 'هذا النوع غير متاح حالياً',
                    'status' => 400,
                ];
            }

            $count = max(1, (int) ($data['count'] ?? $cart->count));

            if ($count > $variant->stock) {
                return [
                    'success' => false,
                    'message' => 'الكمية المطلوبة غير متوفرة',
                    'status' => 400,
                ];
            }

            $cart->update(['count' => $count]);

            return [
                'success' => true,
                'message' => 'تم تحديث الكمية بنجاح',
                'data' => $this->formatCartItem($cart->fresh(['variant.product'])),
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' updateItem');

            return [
                'success' => false,
                'message' => 'فشل تحديث العنصر',
                'status' => 500,
            ];
        }
    }

    public function removeItem(User $user, Cart $cart): array
    {
        try {
            if ($cart->user_id !== $user->id) {
                return [
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذا العنصر',
                    'status' => 403,
                ];
            }

            $cart->delete();

            return [
                'success' => true,
                'message' => 'تم حذف العنصر من السلة بنجاح',
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' removeItem');

            return [
                'success' => false,
                'message' => 'فشل حذف العنصر من السلة',
                'status' => 500,
            ];
        }
    }

    protected function formatCartItem(Cart $item): array
    {
        $variant = $item->variant;
        $product = $variant?->product;

        return [
            'id' => $item->id,
            'variant_id' => $variant?->id,
            'product_name' => $product?->name,
            'product_image' => $product?->image,
            'variant_property' => $variant?->property,
            'variant_price' => (float) $variant?->price,
            'quantity' => (int) $item->count,
            'has_active_offer' => (bool) $variant?->has_active_offer,
            'discount_amount' => (float) ($variant?->has_active_offer ? $variant?->activeOffer->discount_value : 0),
            'new_price' => (float) ($variant?->price - ($variant?->has_active_offer ? $variant?->activeOffer->discount_value : 0)),
            'total_price' => (float) ($variant?->price * $item->count),
            'is_available' => (bool) ($variant?->is_active && $product?->is_active),
            'stock_in_store' => (int) $variant?->stock,
        ];
    }
}
