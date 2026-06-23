<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagementRequests\ChangePasswordUserRequest;
use App\Http\Requests\UserManagementRequests\UpdateProfileUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(protected UserService $service)
    {
    }

    public function getProfileUser(): JsonResponse
    {
        $user = Auth::user();

        return $this->success(
            $this->service->getProfileData($user),
            'تم جلب بيانات المستخدم بنجاح'
        );
    }

    public function updateProfileUser(UpdateProfileUserRequest $request): JsonResponse
    {
        $user = Auth::user();
        $updatedProfile = $this->service->updateProfile($user, $request->validated());

        return $this->success($updatedProfile, 'تم تحديث الملف الشخصي بنجاح');
    }

    public function changePasswordUser(ChangePasswordUserRequest $request): JsonResponse
    {
        $user = Auth::user();
        $result = $this->service->changePassword($user, $request->validated());

        if (! $result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success([], $result['message']);
    }
}
