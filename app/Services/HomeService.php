<?php

namespace App\Services;

use App\Models\Categorie;
use App\Models\Offer;
use App\Models\Product;

class HomeService extends Service
{
    public function userHome(array $filters): array
    {
        $search = $this->normalizeSearch($filters['search'] ?? null);

        return [
            'categories' => $this->getCategories($search),
            'products' => $this->getActiveProducts($search),
            'offers' => $this->getActiveOffers($search),
        ];
    }

    private function normalizeSearch(?string $search): ?string
    {
        $search = trim($search ?? '');

        return $search === '' ? null : $search;
    }

    private function getCategories(?string $search)
    {
        return Categorie::select('id', 'name', 'image')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->get();
    }

    private function getActiveProducts(?string $search)
    {
        return Product::select('id', 'category_id', 'name', 'image', 'description', 'sku_code')
            ->with(['variants' => function ($query) {
                $query->select('id', 'product_id', 'price')
                ->where('is_active' , true);
            }])
            ->where('is_active', true)
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->get();
    }

    private function getActiveOffers(?string $search)
    {
        $offers = Offer::select('id', 'variant_id', 'from', 'to', 'discount_percentage', 'discount_value')
            ->with(['variant' => function ($query) {
                $query->select('id', 'product_id', 'property', 'price', 'stock')
                    ->with(['product' => function ($query) {
                        $query->select('id', 'name', 'image');
                    }]);
            }])
            ->active()
            ->when($search, function ($query, $search) {
                $query->whereHas('variant', function ($variantQuery) use ($search) {
                    $variantQuery
                        ->where('property', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->get();

        return $offers->filter(function ($offer) {
            return $offer->variant && $offer->variant->product;
        })->map(function ($offer) {
            return [
                'id' => $offer->id,
                'from' => $offer->from->toDateString(),
                'to' => $offer->to->toDateString(),
                // 'discount_percentage' => $offer->discount_percentage,
                'discount_value' => $offer->discount_value,
                'product_name' => $offer->variant->product->name,
                'image' => $offer->variant->product->image,
                'property' => $offer->variant->property,
                'variant_price' => $offer->variant->price,
                'variant_stock' => $offer->variant->stock,
            ];
        })->values();
    }
}
