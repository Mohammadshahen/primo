<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrdarRequests\OrdarPriceRequest;
use App\Services\OrdarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrdarController extends Controller
{
    public function __construct(protected OrdarService $service) {}

    public function OrdarPrice(OrdarPriceRequest $request): JsonResponse
    {
        $result = $this->service->calculatePrice($request->validated());
        return $this->success($result, $result['message'] ?? 'تم حساب سعر الطلب بنجاح');
    }
}
