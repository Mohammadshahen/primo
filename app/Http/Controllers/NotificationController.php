<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $service)
    {
    }

    public function index(): JsonResponse
    {
        $notifications = $this->service->getUserNotifications(Auth::user());

        return $this->success($notifications, 'تم جلب الإشعارات بنجاح');
    }

    public function adminNotification(): JsonResponse
    {
        $notifications = $this->service->getUserNotifications(User::where('is_admin', true)->first());

        return $this->success($notifications, 'تم جلب الإشعارات بنجاح');
    }
}
