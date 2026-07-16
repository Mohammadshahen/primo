<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequests\UpdateDeliveryPriceRequest;
use App\Http\Requests\SettingRequests\UpdateDollarValueRequest;
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
        $price = $this->service->getDeliveryPrice();

        return $this->success(['delivery_price' => $price], 'تم جلب سعر التوصيل بنجاح');
    }

    public function updateDollarValue(UpdateDollarValueRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->service->updateDollarValue((float) ($validated['dollar_value'] ?? 1.0));

        return $this->success($result, 'تم تحديث قيمة الدولار بنجاح');
    }

    public function getDollarValue(): JsonResponse
    {
        $value = $this->service->getDollarValue();

        return $this->success(['dollar_value' => $value], 'تم جلب قيمة الدولار بنجاح');
    }
}
