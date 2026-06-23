<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfferRequests\StoreOfferRequest;
use App\Http\Requests\OfferRequests\UpdateOfferRequest;
use App\Models\Offer;
use App\Services\OfferService;

class OfferController extends Controller
{
    protected OfferService $service;

    public function __construct(OfferService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $offers = $this->service->list();

        return $this->success($offers, 'تم جلب العروض بنجاح');
    }

    public function store(StoreOfferRequest $request)
    {
        $result = $this->service->create($request->validated());

        if (! $result['success']) {
            return $this->error($result['message'] ?? 'فشل إنشاء العرض', 400);
        }

        return $this->success($result['data'], 'تم إنشاء العرض بنجاح', 201);
    }

    public function show(Offer $offer)
    {
        return $this->success($this->service->show($offer), 'تم جلب بيانات العرض بنجاح');
    }

    public function update(UpdateOfferRequest $request, Offer $offer)
    {
        $result = $this->service->update($offer, $request->validated());

        if (! $result['success']) {
            return $this->error($result['message'] ?? 'فشل تحديث العرض', 400);
        }

        return $this->success($result['data'], 'تم تحديث العرض بنجاح');
    }

    public function destroy(Offer $offer)
    {
        $result = $this->service->delete($offer);

        if (! $result['success']) {
            return $this->error($result['message'] ?? 'فشل حذف العرض', 400);
        }

        return $this->success([], 'تم حذف العرض بنجاح');
    }
}