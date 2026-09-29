<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Variant;
use App\Services\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OfferPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dollar_discounts_are_stored_in_dollars_and_converted_for_customers(): void
    {
        Bus::fake();
        Setting::setValue('dollar_value', 15000);

        $category = Categorie::create(['name' => 'Category']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Product',
            'sku_code' => 'PRODUCT-1',
        ]);
        $variant = Variant::create([
            'product_id' => $product->id,
            'price' => 10,
            'is_dollar' => true,
            'stock' => 1,
            'property' => 'Default',
            'is_active' => true,
        ]);
        $service = new OfferService();
        $dates = [
            'from' => now()->toDateString(),
            'to' => now()->addDay()->toDateString(),
        ];

        $fixedOffer = $service->create($dates + [
            'variant_id' => $variant->id,
            'discount_value' => 2,
        ])['data'];
        $percentageOffer = $service->create($dates + [
            'variant_id' => $variant->id,
            'discount_percentage' => 10,
        ])['data'];

        $this->assertDatabaseHas('offers', [
            'id' => $fixedOffer->id,
            'discount_value' => 2,
        ]);
        $this->assertDatabaseHas('offers', [
            'id' => $percentageOffer->id,
            'discount_value' => 1,
        ]);
        $this->assertEquals(30000, $fixedOffer->discount_value);
        $this->assertEquals(15000, $percentageOffer->discount_value);

        $storedOffer = Offer::findOrFail($fixedOffer->id);
        $this->assertEquals(2, $storedOffer->getRawOriginal('discount_value'));
    }
}