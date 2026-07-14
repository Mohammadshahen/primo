<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequests\UpdateGeneralSettingsRequest;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;

class GeneralSettingController extends Controller
{
    public function __construct(protected SettingService $service)
    {
    }

    /**
     * Admin: create or update general settings.
     */
    public function update(UpdateGeneralSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->updateGeneralSettings($validated);

        if (isset($result['success']) && $result['success'] === false) {
            return $this->error($result['message'] ?? 'فشل في تحديث الإعدادات', 500);
        }

        return $this->success($result, 'تم تحديث الإعدادات بنجاح');
    }

    /**
     * User: get general settings values.
     */
    public function show(): JsonResponse
    {
        $data = $this->service->getGeneralSettings();

        return $this->success($data, 'تم جلب الإعدادات بنجاح');
    }
}
