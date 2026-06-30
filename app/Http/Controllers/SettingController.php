<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequests\UpdateDeliveryPriceRequest;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(protected SettingService $service)
    {
    }

    public function updateDeliveryPrice(UpdateDeliveryPriceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $price = $validated['price'] ?? 0.0;

        $result = $this->service->updateDeliveryPrice($price);

        return $this->success($result,'تم تحديث سعر التوصيل بنجاح');
    }

    public function getDeliveryPrice(): JsonResponse
    {
        $price = Setting::getValue('delivery_price', 0.0);
        return $this->success(['delivery_price' => $price],'تم جلب سعر التوصيل بنجاح');
    }
}
