<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrdarRequests\ConfirmOrdarRequest;
use App\Services\OrdarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrdarController extends Controller
{
    public function __construct(protected OrdarService $service)
    {
    }

    public function confirmOrdar(ConfirmOrdarRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->service->confirmOrdar($user, $request->validated());

            if (! $result['success']) {
                return $this->error($result['message'] ?? 'فشل تأكيد الطلب', $result['status'] ?? 400);
            }

            return $this->success($result['data'] ?? [], $result['message'] ?? 'تم تأكيد الطلب بنجاح', 201);
        } catch (\Throwable $e) {
            return $this->error('فشل تأكيد الطلب', 500);
        }
    }
}
