<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\Ordar;
use App\Models\Product;
use App\Models\User;
use App\Models\Rating;
use App\Services\FileStorage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService extends Service
{
    public function getProfileData(User $user): array
    {
        return [
            'name' => $user->name,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
        ];
    }

    public function updateProfile(User $user, array $data): array
    {
        try {

            $user->update([
                'name' => $data['name'] ?? $user->name,
                'phone' => $data['phone'] ?? $user->phone,
                'avatar' => isset($data['avatar']) ?
                    FileStorage::fileExists(
                        $data['avatar'],
                        $user->avatar,
                        'avatars',
                        'img'
                    )
                    : $user->avatar,
            ]);

            return $this->getProfileData($user->fresh());
        } catch (\Exception $e) {
            $this->logException($e, __METHOD__ . ' update');
            $this->throwExceptionJson('حدث خطأ أثناء تحديث الملف الشخصي. يرجى المحاولة مرة أخرى.', 500);
        }
    }

    public function changePassword(User $user, array $data): array
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'كلمة المرور القديمة غير صحيحة',
            ];
        }
        try {

            $user->update([
                'password' => Hash::make($data['password']),
            ]);


            return [
                'success' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح',
            ];
        } catch (\Exception $e) {
            $this->logException($e, __METHOD__ . ' changePassword');
            $this->throwExceptionJson('حدث خطأ أثناء تغيير كلمة المرور. يرجى المحاولة مرة أخرى.', 500);
        }
    }

    public function getNotificationSettings(User $user): array
    {
        try {
            return [
                'notification_offer' => (bool) $user->notification_offer,
                'notification_order' => (bool) $user->notification_order,
            ];
        } catch (\Throwable $e) {
            $this->logException($e, __METHOD__ . ' getNotificationSettings');

            $this->throwExceptionJson('حدث خطأ أثناء جلب إعدادات الإشعارات. يرجى المحاولة مرة أخرى.', 500);
        }
    }

    public function updateNotificationSettings(User $user, array $data): array
    {
        try {
            $user->update([
                'notification_offer' => $data['notification_offer'] ?? $user->notification_offer,
                'notification_order' => $data['notification_order'] ?? $user->notification_order,
            ]);

            return [
                'success' => true,
                'message' => 'تم تحديث إعدادات الإشعارات بنجاح',
                'data' => $this->getNotificationSettings($user->fresh()),
            ];
        } catch (\Throwable $e) {
            $this->logException($e, __METHOD__ . ' updateNotificationSettings');

            $this->throwExceptionJson('حدث خطأ أثناء تحديث إعدادات الإشعارات. يرجى المحاولة مرة أخرى.', 500);
        }
    }

    public function toggleFavorite(User $user, Product $product): array
    {
        try {
            $favorite = Favorite::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            if ($favorite) {
                $favorite->delete();

                return [
                    'success' => true,
                    'message' => 'تم حذف المنتج من المفضلة',
                    'data' => [
                        'favorited' => false,
                    ],
                ];
            }

            Favorite::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);

            return [
                'success' => true,
                'message' => 'تم إضافة المنتج إلى المفضلة',
                'data' => [
                    'favorited' => true,
                ],
            ];
        } catch (\Throwable $e) {
            $this->logException($e, __METHOD__ . ' toggleFavorite');

            $this->throwExceptionJson('حدث خطأ أثناء تحديث المفضلة. يرجى المحاولة مرة أخرى.', 500);
        }
    }

    public function getFavoriteProducts(User $user): array
    {
        try {
            $products = Product::with(['variants' => function ($query) {
                    //بدي رجع اقل سعر للمنتج من بين كل الفاريانتس
                    $query->select('id', 'product_id', 'price')
                        ->where('is_active', true)
                        ->orderBy('price', 'asc')
                        ->limit(1);
                }])->whereHas('favorites', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->get()->map(function ($product) {
                    if ($product->variants->isNotEmpty()) {
                        $product->price = $product->variants->first()->price;
                    } else {
                        // تعيين سعر افتراضي أو تجاهل المنتج
                        $product->price = 0; // أو null
                    }

                    $product->is_active = $product->variants->isNotEmpty() ? $product->is_active : false;
                    $product->makeHidden('variants');
                    return $product;
                });


            return [
                'success' => true,
                'message' => 'تم جلب المنتجات المفضلة بنجاح',
                'data' => $products,
            ];
        } catch (\Throwable $e) {
            $this->logException($e, __METHOD__ . ' getFavoriteProducts');

            $this->throwExceptionJson('حدث خطأ أثناء جلب المنتجات المفضلة .', 500);
        }
    }

    public function rateProduct(User $user, Product $product, Ordar $ordar, array $data): array
    {
        $ordar->load('items.variant.product');
        $product_ids = $ordar->items->pluck('variant.product.id')->toArray();
        if ($ordar->user_id !== $user->id || !in_array($product->id, $product_ids) || $ordar->status !== 'completed') {
            $this->throwExceptionJson('لا يمكنك تقييم هذا المنتج.', 403);
        }
        try {
            $ratingValue = (int) ($data['rating'] ?? 0);

            $rating = Rating::updateOrCreate([
                'user_id' => $user->id,
                'product_id' => $product->id,
            ], [
                'rating' => $ratingValue,
            ]);

            return [
                'success' => true,
                'message' => 'تم حفظ تقييم المنتج بنجاح',
                'data' => $rating,
            ];
        } catch (\Throwable $e) {
            $this->logException($e, __METHOD__ . ' rateProduct');

            $this->throwExceptionJson('حدث خطأ أثناء حفظ تقييم المنتج. يرجى المحاولة مرة أخرى.', 500);
        }
    }
}
