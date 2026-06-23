<?php

namespace App\Services;

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
}
