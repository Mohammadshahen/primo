<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
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
                'avatar' => $data['avatar'] ?
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
            return [
                'error' => true,
                'message' => 'حدث خطأ أثناء تحديث الملف الشخصي. يرجى المحاولة مرة أخرى.',
            ];
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
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير كلمة المرور. يرجى المحاولة مرة أخرى.',
            ];
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

            return [
                'error' => true,
                'message' => 'حدث خطأ أثناء جلب إعدادات الإشعارات. يرجى المحاولة مرة أخرى.',
            ];
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

            return [
                'error' => true,
                'message' => 'حدث خطأ أثناء تحديث إعدادات الإشعارات. يرجى المحاولة مرة أخرى.',
            ];
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

            return [
                'error' => true,
                'message' => 'حدث خطأ أثناء تعديل المفضلة. يرجى المحاولة مرة أخرى.',
            ];
        }
    }

    public function getFavoriteProducts(User $user): array
    {
        try {
            $products = Product::active()->
            whereHas('favorites', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();
            return [
                'success' => true,
                'message' => 'تم جلب المنتجات المفضلة بنجاح',
                'data' => $products,
            ];
        } catch (\Throwable $e) {
            $this->logException($e, __METHOD__ . ' getFavoriteProducts');

            return [
                'error' => true,
                'message' => 'حدث خطأ أثناء جلب المنتجات المفضلة. يرجى المحاولة مرة أخرى.',
            ];
        }
    }
}
