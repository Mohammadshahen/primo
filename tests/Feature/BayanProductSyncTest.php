<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Categorie;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Services\BayanProductSyncService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BayanProductSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.bayan.products_url' => 'https://primo.bayanapi.uk/getProducts',
            'services.bayan.page_size' => 100,
            'services.bayan.price_field' => 'Price4',
        ]);
    }

    public function test_sync_updates_only_manually_linked_variants_and_ignores_categories(): void
    {
        $this->assertFalse(Schema::hasColumn('products', 'bayan_id'));
        $this->assertFalse(Schema::hasColumn('categories', 'bayan_id'));
        $this->assertTrue(Schema::hasColumn('variants', 'bayan_id'));

        $category = Categorie::create(['name' => 'تصنيف يدوي']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'منتج يدوي',
            'sku_code' => 'MANUAL-1',
        ]);
        $linked = Variant::create([
            'product_id' => $product->id,
            'bayan_id' => 44,
            'price' => 1,
            'is_dollar' => false,
            'stock' => 2,
            'property' => 'اسم سابق',
            'is_active' => true,
        ]);
        Variant::create([
            'product_id' => $product->id,
            'price' => 5,
            'is_dollar' => false,
            'stock' => 1,
            'property' => 'نوع يدوي غير مربوط',
            'is_active' => true,
        ]);

        Http::fake([
            'primo.bayanapi.uk/*' => Http::response([
                ['Id' => 3, 'Name' => 'مجموعة', 'parent' => 0, 'Kind' => 1],
                [
                    'Id' => 44,
                    'Name' => 'اسم من البيان',
                    'Quantity' => 9,
                    'Price4' => 0.564,
                    'CURRENCY' => 2,
                    'parent' => 999,
                    'Kind' => 0,
                ],
                [
                    'Id' => 55,
                    'Name' => 'عنصر بيان غير مربوط',
                    'Quantity' => 4,
                    'Price4' => 3.5,
                    'CURRENCY' => 1,
                    'parent' => 3,
                    'Kind' => 0,
                ],
            ]),
        ]);

        $this->artisan('bayan:sync-products')->assertExitCode(0);

        $this->assertSame(1, Product::count());
        $this->assertSame(2, Variant::count());
        $this->assertSame($product->id, $linked->fresh()->product_id);
        $this->assertSame('اسم من البيان', $linked->fresh()->property);
        $this->assertSame(9, $linked->fresh()->stock);
        $this->assertEquals(0.564, (float) $linked->fresh()->getRawOriginal('price'));
        $this->assertTrue($linked->fresh()->is_dollar);
        $this->assertSame('نوع يدوي غير مربوط', Variant::whereNull('bayan_id')->firstOrFail()->property);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'تصنيف يدوي']);
    }

    public function test_sync_deactivates_missing_link_and_reactivates_it_when_it_returns(): void
    {
        $product = Product::create(['name' => 'يدوي', 'sku_code' => 'MANUAL-2']);
        $variant = Variant::create([
            'product_id' => $product->id,
            'bayan_id' => 7,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 4,
            'property' => 'قديم',
            'is_active' => true,
        ]);

        Http::fakeSequence()
            ->push([
                ['Id' => 3, 'Name' => 'مجموعة', 'parent' => 0, 'Kind' => 1],
                ['Id' => 8, 'Name' => 'موجود', 'Quantity' => 5, 'Price4' => 1, 'CURRENCY' => 1, 'Kind' => 0],
            ])
            ->push([
                ['Id' => 3, 'Name' => 'مجموعة', 'parent' => 0, 'Kind' => 1],
                ['Id' => 7, 'Name' => 'عاد', 'Quantity' => 6, 'Price4' => 2.25, 'CURRENCY' => 1, 'Kind' => 0],
            ]);

        $this->artisan('bayan:sync-products')->assertExitCode(0);
        $this->assertFalse((bool) $variant->fresh()->is_active);
        $this->assertTrue($variant->fresh()->bayan_unavailable);

        $this->artisan('bayan:sync-products')->assertExitCode(0);
        $this->assertTrue((bool) $variant->fresh()->is_active);
        $this->assertFalse($variant->fresh()->bayan_unavailable);
        $this->assertSame(6, $variant->fresh()->stock);
    }

    public function test_empty_snapshot_does_not_deactivate_linked_variants(): void
    {
        $product = Product::create(['name' => 'يدوي', 'sku_code' => 'MANUAL-3']);
        $variant = Variant::create([
            'product_id' => $product->id,
            'bayan_id' => 7,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 4,
            'property' => 'قديم',
            'is_active' => true,
        ]);

        Http::fake(['primo.bayanapi.uk/*' => Http::response([])]);

        $this->artisan('bayan:sync-products')->assertExitCode(1);
        $this->assertTrue((bool) $variant->fresh()->is_active);
        $this->assertFalse($variant->fresh()->bayan_unavailable);
    }

    public function test_unlinked_endpoint_lists_kind_zero_records_without_importing_them(): void
    {
        $category = Categorie::create(['name' => 'تصنيف']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'منتج', 'sku_code' => 'MANUAL-4']);
        Variant::create([
            'product_id' => $product->id,
            'bayan_id' => 4,
            'price' => 1,
            'is_dollar' => false,
            'stock' => 1,
            'property' => 'مرتبط',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);

        Http::fake([
            'primo.bayanapi.uk/*' => Http::response([
                ['Id' => 3, 'Name' => 'مجموعة', 'parent' => 0, 'Kind' => 1],
                ['Id' => 4, 'Name' => 'مرتبط', 'Quantity' => 1, 'Price4' => 1, 'CURRENCY' => 1, 'Kind' => 0],
                ['Id' => 5, 'Name' => 'غير مرتبط', 'Quantity' => 2, 'Price4' => 3.75, 'CURRENCY' => 2, 'Kind' => 0],
                ['Id' => 12, 'Name' => '3abc', 'Quantity' => 1, 'Price4' => 1, 'CURRENCY' => 1, 'Kind' => 0],
                ['Id' => 123, 'Name' => 'abc', 'Quantity' => 1, 'Price4' => 1, 'CURRENCY' => 1, 'Kind' => 0],
            ]),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/bayan/variants/unlinked')
            ->assertOk()
            ->assertJsonPath('data.0.bayan_id', 5)
            ->assertJsonPath('data.0.name', 'غير مرتبط')
            ->assertJsonPath('data.0.is_dollar', true);

        $keys = collect($response->json('data'))->keyBy('bayan_id');
        $this->assertNotSame($keys[12]['bayan_variant_key'], $keys[123]['bayan_variant_key']);
        $this->assertSame(hash('sha256', "12\0".'3abc'), $keys[12]['bayan_variant_key']);

        $this->assertSame(1, Product::count());
        $this->assertSame(1, Variant::count());
    }

    public function test_admin_can_manually_link_a_bayan_source_row_to_a_product_variant(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::create(['name' => 'منتج يدوي', 'sku_code' => 'MANUAL-7']);
        $variant = Variant::create([
            'product_id' => $product->id,
            'price' => 1,
            'is_dollar' => false,
            'stock' => 1,
            'property' => 'الافتراضي',
        ]);
        $key = hash('sha256', "77\0".'اسم البيان');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/products/'.$product->id, [
                'update_variants' => [[
                    'id' => $variant->id,
                    'bayan_id' => 77,
                    'bayan_variant_key' => $key,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('variants', [
            'id' => $variant->id,
            'bayan_id' => 77,
            'bayan_variant_key' => $key,
        ]);
    }

    public function test_cart_item_note_is_persisted_and_returned(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'منتج مخصص', 'sku_code' => 'MANUAL-5']);
        $variant = Variant::create([
            'product_id' => $product->id,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 10,
            'property' => 'النكهة الأساسية',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/cart', [
                'variant_id' => $variant->id,
                'count' => 1,
                'note' => 'بدون سكر',
            ])
            ->assertCreated()
            ->assertJsonPath('data.note', 'بدون سكر');

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'variant_id' => $variant->id, 'note' => 'بدون سكر']);
    }

    public function test_cart_note_is_copied_to_the_order_item_when_order_is_confirmed(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'منتج حسب الطلب', 'sku_code' => 'MANUAL-6']);
        $variant = Variant::create([
            'product_id' => $product->id,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 5,
            'property' => 'الافتراضي',
            'is_active' => true,
        ]);
        Cart::create([
            'user_id' => $user->id,
            'variant_id' => $variant->id,
            'count' => 1,
            'note' => 'الصلصة على الجانب',
        ]);

        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifictionCreateOrdarForAdmin')->once();
        $notifications->shouldReceive('notifictionCreateOrdarForUser')->once();
        $this->instance(NotificationService::class, $notifications);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/ordar/confirme', ['is_delivery' => false])
            ->assertOk();

        $this->assertDatabaseHas('ordar_itams', [
            'variant_id' => $variant->id,
            'count' => 1,
            'note' => 'الصلصة على الجانب',
        ]);
    }

    public function test_sync_batches_variant_updates(): void
    {
        config(['services.bayan.page_size' => 1000]);
        $product = Product::create(['name' => 'دفعة', 'sku_code' => 'BATCH-1']);
        $source = [];
        $insertRows = [];

        for ($id = 1; $id <= 501; $id++) {
            $insertRows[] = [
                'product_id' => $product->id,
                'bayan_id' => $id,
                'price' => 1,
                'is_dollar' => false,
                'stock' => 1,
                'property' => 'سابق '.$id,
                'is_active' => true,
                'bayan_unavailable' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $source[] = [
                'Id' => $id,
                'Name' => 'اسم '.$id,
                'Quantity' => 4,
                'Price4' => 2.5,
                'CURRENCY' => 1,
                'Kind' => 0,
            ];
        }

        DB::table('variants')->insert($insertRows);
        Http::fake(['primo.bayanapi.uk/*' => Http::response($source)]);

        $variantUpsertQueries = 0;
        DB::listen(function ($query) use (&$variantUpsertQueries): void {
            if (str_starts_with(strtolower(ltrim($query->sql)), 'insert into "variants"')) {
                $variantUpsertQueries++;
            }
        });

        $this->artisan('bayan:sync-products')->assertExitCode(0);

        $this->assertSame(501, Variant::where('stock', 4)->count());
        $this->assertSame(8, $variantUpsertQueries);
    }

    public function test_negative_stock_count_in_sync_result_includes_only_linked_variants(): void
    {
        $product = Product::create(['name' => 'مخزون', 'sku_code' => 'STOCK-1']);
        Variant::create([
            'product_id' => $product->id,
            'bayan_id' => 1,
            'price' => 1,
            'is_dollar' => false,
            'stock' => 1,
            'property' => 'مرتبط',
            'is_active' => true,
        ]);

        Http::fake([
            'primo.bayanapi.uk/*' => Http::response([
                ['Id' => 1, 'Name' => 'مرتبط', 'Quantity' => 5, 'Price4' => 1, 'CURRENCY' => 1, 'Kind' => 0],
                ['Id' => 2, 'Name' => 'غير مرتبط', 'Quantity' => -8, 'Price4' => 1, 'CURRENCY' => 1, 'Kind' => 0],
            ]),
        ]);

        $result = app(BayanProductSyncService::class)->sync();

        $this->assertSame(0, $result['negative_stock_variants']);
    }

    public function test_unchanged_linked_variants_are_not_written_or_timestamped(): void
    {
        $product = Product::create(['name' => 'ثابت', 'sku_code' => 'UNCHANGED-1']);
        $name = 'اسم ثابت';
        $updatedAt = now()->subDay()->startOfSecond();
        $variant = Variant::create([
            'product_id' => $product->id,
            'bayan_id' => 901,
            'bayan_variant_key' => hash('sha256', '901'."\0".$name),
            'bayan_currency_id' => 1,
            'bayan_unavailable' => false,
            'price' => 0.564,
            'is_dollar' => false,
            'stock' => 7,
            'property' => $name,
            'is_active' => true,
        ]);
        $variant->forceFill(['updated_at' => $updatedAt])->save();

        Http::fake([
            'primo.bayanapi.uk/*' => Http::response([[
                'Id' => 901,
                'Name' => $name,
                'Quantity' => 7,
                'Price4' => 0.564,
                'CURRENCY' => 1,
                'Kind' => 0,
            ]]),
        ]);

        $variantUpsertQueries = 0;
        DB::listen(function ($query) use (&$variantUpsertQueries): void {
            if (str_starts_with(strtolower(ltrim($query->sql)), 'insert into "variants"')) {
                $variantUpsertQueries++;
            }
        });

        $result = app(BayanProductSyncService::class)->sync();

        $this->assertSame(0, $result['variants_updated']);
        $this->assertSame(0, $variantUpsertQueries);
        $this->assertSame($updatedAt->toDateTimeString(), $variant->fresh()->updated_at->toDateTimeString());
    }
}
