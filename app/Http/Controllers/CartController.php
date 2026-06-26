<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartRequests\AddCartItemRequest;
use App\Http\Requests\CartRequests\UpdateCartItemRequest;
use App\Models\Cart;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct(protected CartService $service)
    {
    }

    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->service->listItems($user->id);

            if (! $result['success']) {
                return $this->error($result['message'] ?? 'فشل جلب السلة', $result['status'] ?? 500);
            }

            return $this->success($result['data'] ?? [], 'تم جلب عناصر السلة بنجاح');
        } catch (\Throwable $e) {
            return $this->error('فشل جلب السلة', 500);
        }
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->service->addItem($user, $request->validated());

            if (! $result['success']) {
                return $this->error($result['message'] ?? 'فشل إضافة العنصر للسلة', $result['status'] ?? 400);
            }

            return $this->success($result['data'] ?? [], $result['message'] ?? 'تمت إضافة العنصر للسلة بنجاح', 201);
        } catch (\Throwable $e) {
            return $this->error('فشل إضافة العنصر للسلة', 500);
        }
    }

    public function update(UpdateCartItemRequest $request, Cart $cart): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->service->updateItem($user, $cart, $request->validated());

            if (! $result['success']) {
                return $this->error($result['message'] ?? 'فشل تحديث العنصر', $result['status'] ?? 400);
            }

            return $this->success($result['data'] ?? [], $result['message'] ?? 'تم تحديث العنصر بنجاح');
        } catch (\Throwable $e) {
            return $this->error('فشل تحديث العنصر', 500);
        }
    }

    public function destroy(Cart $cart): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->service->removeItem($user, $cart);

            if (! $result['success']) {
                return $this->error($result['message'] ?? 'فشل حذف العنصر', $result['status'] ?? 400);
            }

            return $this->success([], $result['message'] ?? 'تم حذف العنصر من السلة بنجاح');
        } catch (\Throwable $e) {
            return $this->error('فشل حذف العنصر', 500);
        }
    }
}
