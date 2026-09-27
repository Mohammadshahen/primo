<?php

namespace App\Http\Controllers;

use App\Models\Variant;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    public function index(Request $request)
    {
        abort_unless((bool) $request->user()?->is_admin, 403);

        $filters = $request->validate([
            'linked' => 'sometimes|in:0,1',
            'search' => 'nullable|string|max:255',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Variant::query()
            ->with('product:id,name')
            ->select([
                'id',
                'product_id',
                'bayan_id',
                'property',
                'price',
                'is_dollar',
                'stock',
                'is_active',
            ]);

        if (isset($filters['linked'])) {
            $filters['linked'] === '1'
                ? $query->whereNotNull('product_id')
                : $query->whereNull('product_id');
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($query) use ($search): void {
                $query->where('property', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search): void {
                        $productQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $variants = $query->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 50)
            ->through(fn (Variant $variant) => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product?->name,
                'bayan_id' => $variant->bayan_id,
                'property' => $variant->property,
                'price' => $variant->price,
                'is_dollar' => $variant->is_dollar,
                'stock' => $variant->stock,
                'is_active' => $variant->is_active,
            ]);

        return $this->paginate($variants, 'تم جلب الأنواع بنجاح');
    }

    public function toggleActive(Request $request, Variant $variant)
    {
        abort_unless((bool) $request->user()?->is_admin, 403);

        $variant->update([
            'is_active' => ! $variant->is_active,
            'bayan_unavailable' => false,
        ]);

        return $this->success($variant->only(['id', 'product_id', 'is_active']), 'تم تغيير حالة النوع بنجاح');
    }
}
