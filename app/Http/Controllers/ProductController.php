<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequests\StoreProductRequest;
use App\Http\Requests\ProductRequests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Variant;
use App\Services\ProductService;

class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $products = $this->service->list();

        return $this->success($products, 'تم جلب المنتجات بنجاح');
    }

    public function store(StoreProductRequest $request)
    {
        $result = $this->service->create($request->validated());

        if (! $result['success']) {
            return $this->error($result['message'] ?? 'فشل إنشاء المنتج', 400);
        }

        return $this->success($result['data'], 'تم إنشاء المنتج بنجاح', 201);
    }

    public function show(Product $product)
    {
        $data = $this->service->show($product);
        return $this->success($data, 'تم جلب بيانات المنتج بنجاح');
    }

    public function getProductById(Product $product)
    {
        try {
            
            $result = $this->service->getProductById($product);

            if (! $result['success']) {
                return $this->error($result['message'] ?? 'فشل جلب المنتج', $result['status'] ?? 400);
            }

            return $this->success($result['data'], 'تم جلب بيانات المنتج بنجاح');
        } catch (\Throwable $e) {
            return $this->error('فشل جلب المنتج', 500);
        }
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $result = $this->service->update($product, $request->validated());

        if (! $result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($result['data'], 'تم تحديث المنتج بنجاح', 200);
    }

    public function destroy(Product $product)
    {
        $result = $this->service->delete($product);

        if (! $result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success([], 'تم حذف المنتج بنجاح');
    }

    public function toggleStatus(Product $product)
    {
        $result = $this->service->toggleStatus($product);

        if (! $result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success($result['data'], 'تم تحديث حالة المنتج بنجاح');
    }

    public function deleteVariant(Variant $variant)
    {
        $result = $this->service->deleteVariant($variant);

        if (! $result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success([], 'تم حذف النوع بنجاح');
    }
}
