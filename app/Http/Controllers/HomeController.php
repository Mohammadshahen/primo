<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Services\HomeService;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    protected HomeService $service;

    public function __construct(HomeService $service)
    {
        $this->service = $service;
    }

    public function userHome(HomeRequest $request): JsonResponse
    {
        $data = $this->service->userHome($request->validated());

        return $this->success($data, 'تم جلب بيانات الصفحة الرئيسية بنجاح');
    }

    public function adminHome(): JsonResponse
    {
        $data = $this->service->adminHome();

        return $this->success($data, 'تم جلب بيانات لوحة تحكم المشرف بنجاح');
    }
}
