<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrdarRequests\AdminOrdarFilterRequest;
use App\Http\Requests\OrdarRequests\OrdarPriceRequest;
use App\Models\Ordar;
use App\Services\OrdarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrdarController extends Controller
{
    public function __construct(protected OrdarService $service) {}

    public function getAllOrdar(AdminOrdarFilterRequest $request): JsonResponse
    {
        $ordars = $this->service->getAllOrdars($request->validated());
        return $this->success($ordars, 'تم جلب الطلبات بنجاح');
    }

    public function getUserOrdars(AdminOrdarFilterRequest $request): JsonResponse
    {
        $user_id = Auth::id();
        $ordars = $this->service->getUserOrdars($request->validated(), $user_id);
        return $this->success($ordars, 'تم جلب الطلبات بنجاح');
    }

    public function getSingleOrdarForUser(Ordar $ordar): JsonResponse
    {
        $user_id = Auth::id();
        $ordar = $this->service->getSingleOrdarForUser($ordar, $user_id);
        return $this->success($ordar, 'تم جلب الطلب بنجاح');
    }

    public function getSingleOrdar(Ordar $ordar): JsonResponse
    {
        $ordar = $this->service->getSingleOrdar($ordar);
        return $this->success($ordar, 'تم جلب الطلب بنجاح');
    }

    public function changeOrdarStatus(AdminOrdarFilterRequest $request,Ordar $ordar): JsonResponse
    {
        $ordar = $this->service->changeOrdarStatus($request->validated(),$ordar);
        return $this->success($ordar, 'تم تغيير حالة الطلب بنجاح');
    }

    public function OrdarPrice(OrdarPriceRequest $request): JsonResponse
    {
        $result = $this->service->calculatePrice($request->validated());
        return $this->success($result, $result['message'] ?? 'تم حساب سعر الطلب بنجاح');
    }

    public function confirmeOrdar(OrdarPriceRequest $request): JsonResponse
    {
        $result = $this->service->confirmeOrdar($request->validated());
        return $this->success($result, 'تم تأكيد الطلب بنجاح ');
    }
}
