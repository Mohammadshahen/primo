<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressRequests\StoreAddressRequest;
use App\Http\Requests\AddressRequests\UpdateAddressRequest;
use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function __construct(protected AddressService $service)
    {
    }

    public function index(): JsonResponse
    {
        $result = $this->service->list(Auth::user());

        if (! $result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($result['data'], 'تم جلب العناوين بنجاح');
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $result = $this->service->create(Auth::user(), $request->validated());

        if (! $result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($result['data'], $result['message'], 201);
    }

    public function show(Address $address): JsonResponse
    {
        if ($address->user_id !== Auth::id()) {
            return $this->error('غير مصرح لك بالوصول إلى هذا العنوان', 403);
        }

        return $this->success($address, 'تم جلب العنوان بنجاح');
    }

    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        if ($address->user_id !== Auth::id()) {
            return $this->error('غير مصرح لك بتعديل هذا العنوان', 403);
        }

        $result = $this->service->update($address, $request->validated());

        if (! $result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($result['data'], $result['message']);
    }

    public function destroy(Address $address): JsonResponse
    {
        if ($address->user_id !== Auth::id()) {
            return $this->error('غير مصرح لك بحذف هذا العنوان', 403);
        }

        $result = $this->service->delete($address);

        if (! $result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success([], $result['message']);
    }
}
