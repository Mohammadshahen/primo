<?php

namespace App\Services;

use App\Models\Categorie;
use App\Models\Offer;
use App\Models\Ordar;
use App\Models\Product;
use App\Models\Suggestion;
use Carbon\Carbon;

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
        $products =  Product::select('id', 'category_id', 'name', 'image')
            ->with(['variants' => function ($query) {
                //بدي رجع اقل سعر للمنتج من بين كل الفاريانتس
                $query->select('id', 'product_id', 'price')
                    ->where('is_active', true)
                    ->orderBy('price', 'asc')
                    ->limit(1);
            }, 'category' => function ($query) {
                $query->select('id', 'name');
            }])
            ->where('is_active', true)
            // بدي البحث على اسم المنتج او اسم القسم
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    });
            })
            ->get();
        return $products->filter(function ($product) {
            return $product->variants->isNotEmpty();
        })->map(function ($product) {
            return [
                'id' => $product->id,
                'category_name' => $product->category->name,
                'name' => $product->name,
                'image' => $product->image,
                'price' => $product->variants->first()->price ?? null,
                'ratings' => $product->ratings->avg('rating'),
            ];
        })->values();
    }

    private function getActiveOffers(?string $search)
    {
        $offers = Offer::select('id', 'variant_id', 'from', 'to', 'discount_percentage', 'discount_value')
            ->with(['variant.product.ratings'])
            ->whereHas('variant', function ($query) {
                $query->Available();
            })
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
            $offer->variant->product->rating = $offer->variant->product->ratings->avg('rating') ?? 0;
            $offer->variant->product->makeHidden('ratings');
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
                'variant_product' => $offer->variant,
            ];
        })->values();
    }

    public function adminHome(): array
    {
        try {
            // Week starts on Sunday
            $startOfWeek = Carbon::now()->startOfWeek(Carbon::SUNDAY)->startOfDay();
            $endOfWeek = (clone $startOfWeek)->endOfWeek(Carbon::SUNDAY)->endOfDay();

            $weeklyTotal = (float) Ordar::where('status', 'completed')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->sum('total_amount');

            $weeklyOrdersCount = Ordar::query()
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->count();

            return [
                'total_amount' => (float) Ordar::where('status', 'completed')->sum('total_amount'),
                'weekly_total_amount' => $weeklyTotal,
                'pending_orders_count' => Ordar::query()->where('status', 'pending')->count(),
                'weekly_orders_count' => $weeklyOrdersCount,
                'products_count' => Product::query()->count(),
                'pending_orders' => $this->getPendingOrders(),
                'pending_suggestions' => $this->getPendingSuggestions(),
            ];
        } catch (\Throwable $exception) {
            $this->logException($exception, 'admin home dashboard');
            $this->throwExceptionJson('فشل في جلب بيانات لوحة تحكم المشرف');
        }
    }

    private function getPendingOrders()
    {
        return Ordar::query()
            ->with(['user'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();
        // ->map(function (Ordar $ordar) {
        //     return [
        //         'id' => $ordar->id,
        //         'user_name' => $ordar->user?->name,
        //         'address' => $ordar->address?->name,
        //         'status' => $ordar->status,
        //         'amount' => (float) $ordar->amount,
        //         'delivery_amount' => (float) $ordar->delivere_amount,
        //         'total_amount' => (float) $ordar->total_amount,
        //         'created_at' => $ordar->created_at->toDateTimeString(),
        //     ];
        // });
    }

    private function getPendingSuggestions()
    {
        return Suggestion::query()
            ->select('id', 'name', 'description', 'user_id', 'status', 'created_at')
            ->with(['user'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Suggestion $suggestion) {
                return [
                    'id' => $suggestion->id,
                    'name' => $suggestion->name,
                    'description' => $suggestion->description,
                    'status' => $suggestion->status,
                    'created_at' => $suggestion->created_at ? $suggestion->created_at->toDateTimeString() : now()->toDateTimeString(),
                    'user' => $suggestion->user,
                ];
            });
    }
}
