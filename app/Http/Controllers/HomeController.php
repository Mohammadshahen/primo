<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Services\HomeService;

class HomeController extends Controller
{
    protected HomeService $service;

    public function __construct(HomeService $service)
    {
        $this->service = $service;
    }

    public function userHome(HomeRequest $request)
    {
        $data = $this->service->userHome($request->validated());

        return $this->success($data, 'تم جلب بيانات الصفحة الرئيسية بنجاح');
    }
}
