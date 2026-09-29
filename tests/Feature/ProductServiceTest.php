<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Variant;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_details_include_only_active_variants(): void
    {
        $category = Categorie::create(['name' => 'Category']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Product',
            'sku_code' => 'PRODUCT-1',
        ]);
        $activeVariant = Variant::create([
            'product_id' => $product->id,
            'price' => 10.29,
            'is_dollar' => false,
            'stock' => 1,
            'property' => 'Active',
            'is_active' => true,
        ]);
        Variant::create([
            'product_id' => $product->id,
            'price' => 20,
            'is_dollar' => false,
            'stock' => 1,
            'property' => 'Inactive',
            'is_active' => false,
        ]);
        Offer::create([
            'variant_id' => $activeVariant->id,
            'from' => now()->subDay()->toDateString(),
            'to' => now()->addDay()->toDateString(),
            'discount_value' => 2.17,
        ]);

        $result = app(ProductService::class)->getProductById($product);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['data']['variants']);
        $this->assertSame($activeVariant->id, $result['data']['variants'][0]['id']);
        $this->assertSame(10.29, $result['data']['variants'][0]['price']);
        $this->assertSame(2.17, $result['data']['variants'][0]['discount_amount']);
        $this->assertSame(8.12, $result['data']['variants'][0]['new_price']);
    }
}