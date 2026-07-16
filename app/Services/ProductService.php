<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Variant;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductService extends Service
{
    public function list()
    {
        return Product::with('category:id,name','variants')
            // ->select('id', 'category_id', 'name', 'image', 'description', 'sku_code', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()->map(function ($product) {
                $product->total_stack = $product->variants->sum('stock') ?? 0;
                $product->lowest_price = $product->variants->min('price') ?? 0;
                return $product;
            });
    }

    public function show(Product $product)
    {
        $product->load(['category:id,name', 'variants']);
        $product->variants->each(function ($variant) {
            $variant->has_active_offer = $variant->has_active_offer;
            $variant->offer_id = $variant->offer_id;
            $variant->makeHidden(['activeOffer']);
        });

        return $product;
    }

    public function getProductById(Product $product): array
    {
        try {
            $product->load(['category:id,name', 'variants', 'variants.activeOffer', 'ratings']);
            $data = [
                'id' => $product->id,
                'category_name' => $product->category->name,
                'name' => $product->name,
                'image' => $product->image,
                'description' => $product->description,
                'sku_code' => $product->sku_code,
                'ratings' => $product->ratings->avg('rating'),
                'ratings_count' => $product->ratings->count(),
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'price' => $variant->price,
                        'is_dollar' => $variant->is_dollar,
                        'stock' => $variant->stock,
                        'property' => $variant->property,
                        'is_active' => $variant->is_active,
                        'has_active_offer' => $variant->has_active_offer,
                        'discount_amount' => $variant->has_active_offer ? $variant->activeOffer->discount_value : 0,
                        'new_price' => $variant->price - ($variant->has_active_offer ? $variant->activeOffer->discount_value : 0),
                    ];
                }),
            ];

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' getProductById');

            return [
                'success' => false,
                'message' => 'فشل جلب المنتج',
                'status' => 500,
            ];
        }
    }

    public function create(array $data): array
    {
        try {
            DB::beginTransaction();
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'image' => isset($data['image']) ? FileStorage::storeFile($data['image'], 'products', 'img') : null,
                'description' => $data['description'] ?? null,
                'sku_code' => strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            ]);

            foreach ($data['variants'] as $variantData) {
                $product->variants()->create([
                    'price' => $variantData['price'] ?? 1,
                    'is_dollar' => $variantData['is_dollar'] ?? false,
                    'stock' => $variantData['stock'] ?? 1,
                    'property' => $variantData['property'] ?? null,
                    'is_active' => true,
                ]);
            }

            DB::commit();
            return [
                'success' => true,
                'data' => $product->load(['category:id,name', 'variants']),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' create');

            return [
                'success' => false,
                'message' => 'فشل إنشاء المنتج',
            ];
        }
    }

    public function update(Product $product, array $data): array
    {
        try {
            DB::beginTransaction();



            if (isset($data['update_variants'])) {
                foreach ($data['update_variants'] as $variantData) {
                    $variant = $product->variants()->find($variantData['id']);
                    if ($variant) {
                        $variant->update([
                            'price' => $variantData['price'] ?? $variant->price,
                            'is_dollar' => $variantData['is_dollar'] ?? $variant->is_dollar,
                            'stock' => $variantData['stock'] ?? $variant->stock,
                            'property' => $variantData['property'] ?? $variant->property,
                            'is_active' => $variantData['is_active'] ?? $variant->is_active,
                        ]);
                    }
                }
            }

            if (isset($data['add_variants'])) {
                foreach ($data['add_variants'] as $variantData) {
                    $product->variants()->create([
                        'price' => $variantData['price'] ?? 1,
                        'is_dollar' => $variantData['is_dollar'] ?? false,
                        'stock' => $variantData['stock'] ?? 1,
                        'property' => $variantData['property'] ?? null,
                        'is_active' => true,
                    ]);
                }
            }
            $productStatus = $data['is_active'] ?? $product->is_active;
            if ($productStatus == true) {
                $variants = Variant::where('product_id', $product->id)->get();
                $productStatus = false;
                foreach ($variants as $variant) {
                    if ($variant->is_active == true) {
                        $productStatus = true;
                        break;
                    }
                }
            }

            $product->update([
                'category_id' => $data['category_id'] ?? $product->category_id,
                'name' => $data['name'] ?? $product->name,
                'image' => isset($data['image'])
                    ? FileStorage::fileExists($data['image'], $product->image, 'products', 'img')
                    : $product->image,
                'description' => $data['description'] ?? $product->description,
                'is_active' => $productStatus,
            ]);

            DB::commit();
            return [
                'success' => true,
                'data' => $product->load(['category:id,name', 'variants']),
            ];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logException($e, __METHOD__ . ' update');

            return [
                'success' => false,
                'message' => 'فشل تحديث المنتج',
            ];
        }
    }

    public function delete(Product $product): array
    {
        try {
            if ($product->image) {
                FileStorage::deleteFile($product->image);
            }

            $product->delete();

            return [
                'success' => true,
                'data' => [],
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' delete');

            return [
                'success' => false,
                'message' => 'فشل حذف المنتج',
            ];
        }
    }

    public function toggleStatus(Product $product)
    {
        $productStatus = ! $product->is_active;
        if ($productStatus == true) {
            $variants = Variant::where('product_id', $product->id)->get();
            $productStatus = false;
            foreach ($variants as $variant) {
                if ($variant->is_active == true) {
                    $productStatus = true;
                    break;
                }
            }
            if ($productStatus == false) {
                return $this->throwExceptionJson('لا يمكن تفعيل المنتج لأن جميع أنواعه غير مفعلة', 400);
            }
        }
        try {
            $product->update(['is_active' => $productStatus]);

            return [
                'success' => true,
                'data' => $product->only('id', 'is_active'),
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' toggleStatus');

            return [
                'success' => false,
                'message' => 'فشل تحديث حالة المنتج',
            ];
        }
    }

    public function deleteVariant(Variant $variant): array
    {
        try {
            $product_id = $variant->product_id;
            $variant->delete();
            $this->checkProductAvailability($product_id);

            return [
                'success' => true,
                'data' => [],
            ];
        } catch (Exception $e) {
            $this->logException($e, __METHOD__ . ' delete');

            return [
                'success' => false,
                'message' => 'فشل حذف المتغير',
            ];
        }
    }

    public function checkProductAvailability($product_id)
    {

        $product = Product::with(['variants' => function ($query) {
            $query->where('is_active', true);
        }])->find($product_id);

        if ($product->variants->isEmpty()) {
            $product->update(['is_active' => false]);
        }
    }
}
