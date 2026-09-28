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
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
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

    public function test_sync_imports_kind_zero_variants_and_ignores_kind_one_records(): void
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
        $this->assertSame(3, Variant::count());
        $this->assertSame($product->id, $linked->fresh()->product_id);
        $this->assertSame('اسم من البيان', $linked->fresh()->property);
        $this->assertSame(9, $linked->fresh()->stock);
        $this->assertEquals(0.564, (float) $linked->fresh()->getRawOriginal('price'));
        $this->assertTrue($linked->fresh()->is_dollar);
        $this->assertDatabaseHas('variants', [
            'bayan_id' => 55,
            'product_id' => null,
            'property' => 'عنصر بيان غير مربوط',
            'stock' => 4,
        ]);
        $this->assertDatabaseMissing('variants', ['bayan_id' => 3]);
        $this->assertSame('نوع يدوي غير مربوط', Variant::whereNull('bayan_id')->firstOrFail()->property);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'تصنيف يدوي']);
    }

    public function test_sync_command_skips_when_another_sync_holds_the_lock(): void
    {
        Http::fake();
        $lock = Cache::lock('bayan-sync', 3600);
        $this->assertTrue($lock->get());

        try {
            $this->artisan('bayan:sync-products')
                ->expectsOutput('Bayan sync is already running. Skipping this run.')
                ->assertExitCode(0);

            Http::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    public function test_bayan_fetches_all_pages_using_limit_and_incrementing_offset(): void
    {
        config(['services.bayan.page_size' => 2]);
        Http::fakeSequence()
            ->push([
                ['Id' => 801, 'Name' => 'أول', 'Quantity' => 1, 'Price4' => 1, 'CURRENCY' => 1, 'Kind' => 0],
                ['Id' => 802, 'Name' => 'ثان', 'Quantity' => 1, 'Price4' => 1, 'CURRENCY' => 1, 'Kind' => 0],
            ])
            ->push([
                ['Id' => 803, 'Name' => 'ثالث', 'Quantity' => 1, 'Price4' => 1, 'CURRENCY' => 1, 'Kind' => 0],
            ]);

        $records = app(BayanProductSyncService::class)->unlinkedVariants();

        $this->assertCount(3, $records);
        Http::assertSent(fn (HttpRequest $request): bool =>
            str_contains($request->url(), 'limit=2')
            && str_contains($request->url(), 'offset=0')
        );
        Http::assertSent(fn (HttpRequest $request): bool =>
            str_contains($request->url(), 'limit=2')
            && str_contains($request->url(), 'offset=2')
        );
        Http::assertSentCount(2);
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
        $this->assertTrue($variant->fresh()->is_auto_deactivated);

        $this->artisan('bayan:sync-products')->assertExitCode(0);
        $this->assertTrue((bool) $variant->fresh()->is_active);
        $this->assertFalse($variant->fresh()->bayan_unavailable);
        $this->assertSame(6, $variant->fresh()->stock);
    }

    public function test_zero_stock_deactivates_variant_and_products_without_active_variants(): void
    {
        $emptyProduct = Product::create(['name' => 'نفد مخزونه', 'sku_code' => 'OUT-OF-STOCK-1']);
        $emptyVariant = Variant::create([
            'product_id' => $emptyProduct->id,
            'bayan_id' => 71,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 3,
            'property' => 'نفد',
            'is_active' => true,
        ]);
        $manuallyDisabledProduct = Product::create([
            'name' => 'معطل يدويًا',
            'sku_code' => 'OUT-OF-STOCK-MANUAL',
            'is_active' => false,
        ]);
        $manuallyDisabledVariant = Variant::create([
            'product_id' => $manuallyDisabledProduct->id,
            'bayan_id' => 73,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 3,
            'property' => 'معطل يدويًا',
            'is_active' => true,
        ]);
        $manualVariantProduct = Product::create([
            'name' => 'نوع معطل يدويًا',
            'sku_code' => 'VARIANT-MANUALLY-OFF',
        ]);
        $manualVariant = Variant::create([
            'product_id' => $manualVariantProduct->id,
            'bayan_id' => 74,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 0,
            'property' => 'تعطيل يدوي',
            'is_active' => false,
            'is_auto_deactivated' => false,
        ]);
        $availableProduct = Product::create(['name' => 'متوفر جزئيًا', 'sku_code' => 'OUT-OF-STOCK-2']);
        $unavailableVariant = Variant::create([
            'product_id' => $availableProduct->id,
            'bayan_id' => 72,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 3,
            'property' => 'نفد',
            'is_active' => true,
        ]);
        $activeVariant = Variant::create([
            'product_id' => $availableProduct->id,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 3,
            'property' => 'متوفر',
            'is_active' => true,
        ]);

        Http::fakeSequence()
            ->push([
                ['Id' => 71, 'Name' => 'نفد', 'Quantity' => 0, 'Price4' => 2, 'CURRENCY' => 1, 'Kind' => 0],
                ['Id' => 73, 'Name' => 'معطل يدويًا', 'Quantity' => 0, 'Price4' => 2, 'CURRENCY' => 1, 'Kind' => 0],
                ['Id' => 74, 'Name' => 'تعطيل يدوي', 'Quantity' => 0, 'Price4' => 2, 'CURRENCY' => 1, 'Kind' => 0],
                ['Id' => 72, 'Name' => 'نفد', 'Quantity' => 0, 'Price4' => 2, 'CURRENCY' => 1, 'Kind' => 0],
            ])
            ->push([
                ['Id' => 71, 'Name' => 'عاد', 'Quantity' => 4, 'Price4' => 2, 'CURRENCY' => 1, 'Kind' => 0],
                ['Id' => 73, 'Name' => 'عاد يدويًا', 'Quantity' => 4, 'Price4' => 2, 'CURRENCY' => 1, 'Kind' => 0],
                ['Id' => 74, 'Name' => 'تعطيل يدوي', 'Quantity' => 4, 'Price4' => 2, 'CURRENCY' => 1, 'Kind' => 0],
            ]);

        app(BayanProductSyncService::class)->sync();

        $this->assertFalse((bool) $emptyVariant->fresh()->is_active);
        $this->assertSame(0, $emptyVariant->fresh()->stock);
        $this->assertTrue($emptyVariant->fresh()->bayan_unavailable);
        $this->assertTrue($emptyVariant->fresh()->is_auto_deactivated);
        $this->assertFalse((bool) $emptyProduct->fresh()->is_active);
        $this->assertTrue($emptyProduct->fresh()->bayan_auto_disabled);
        $this->assertFalse((bool) $unavailableVariant->fresh()->is_active);
        $this->assertTrue((bool) $availableProduct->fresh()->is_active);
        $this->assertTrue((bool) $activeVariant->fresh()->is_active);

        app(BayanProductSyncService::class)->sync();

        $this->assertTrue((bool) $emptyVariant->fresh()->is_active);
        $this->assertTrue((bool) $emptyProduct->fresh()->is_active);
        $this->assertFalse($emptyProduct->fresh()->bayan_auto_disabled);
        $this->assertTrue((bool) $manuallyDisabledVariant->fresh()->is_active);
        $this->assertFalse((bool) $manuallyDisabledProduct->fresh()->is_active);
        $this->assertFalse((bool) $manualVariant->fresh()->is_active);
        $this->assertFalse($manualVariant->fresh()->is_auto_deactivated);
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

    public function test_product_update_rejects_variant_data_mutation(): void
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
        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/products/'.$product->id, [
                'update_variants' => [[
                    'id' => $variant->id,
                    'bayan_id' => 77,
                    'property' => 'تغيير غير مسموح',
                ]],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('variants', [
            'id' => $variant->id,
            'bayan_id' => null,
            'property' => 'الافتراضي',
        ]);
    }

    public function test_product_creation_links_existing_variant_ids_without_creating_variants(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $existingProduct = Product::create(['name' => 'مصدر يدوي', 'sku_code' => 'SOURCE-1']);
        $variants = collect(['صغير', 'كبير'])->map(fn (string $property) => Variant::create([
            'product_id' => $existingProduct->id,
            'price' => 1.25,
            'is_dollar' => false,
            'stock' => 5,
            'property' => $property,
            'is_active' => true,
        ]));
        $variantCount = Variant::count();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/products', [
                'name' => 'منتج جديد',
                'variant_ids' => $variants->pluck('id')->all(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'منتج جديد');

        $createdProduct = Product::where('name', 'منتج جديد')->firstOrFail();
        $this->assertSame($variantCount, Variant::count());
        $this->assertSame(2, $createdProduct->variants()->count());
        $this->assertSame(
            [$createdProduct->id],
            Variant::whereIn('id', $variants->pluck('id'))->pluck('product_id')->unique()->all()
        );
    }

    public function test_product_update_links_existing_variants_and_rejects_new_variant_creation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $sourceProduct = Product::create(['name' => 'مصدر', 'sku_code' => 'UPDATE-SOURCE']);
        $targetProduct = Product::create(['name' => 'هدف', 'sku_code' => 'UPDATE-TARGET']);
        $variant = Variant::create([
            'product_id' => $sourceProduct->id,
            'price' => 1,
            'is_dollar' => false,
            'stock' => 3,
            'property' => 'موجود',
            'is_active' => true,
        ]);
        $variantToDetach = Variant::create([
            'product_id' => $targetProduct->id,
            'price' => 1.5,
            'is_dollar' => false,
            'stock' => 2,
            'property' => 'يفك ربطه',
            'is_active' => true,
        ]);
        $variantCount = Variant::count();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/products/'.$targetProduct->id, [
                'variant_ids' => [$variant->id],
            ])
            ->assertOk();

        $this->assertSame($targetProduct->id, $variant->fresh()->product_id);
        $this->assertNull($variantToDetach->fresh()->product_id);
        $this->assertSame($variantCount, Variant::count());

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/products/'.$targetProduct->id, [
                'add_variants' => [[
                    'property' => 'جديد',
                    'price' => 2,
                    'stock' => 5,
                ]],
            ])
            ->assertUnprocessable();

        $this->assertSame($variantCount, Variant::count());
    }

    public function test_deleting_product_preserves_variants_and_clears_product_id(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::create(['name' => 'سيحذف', 'sku_code' => 'DELETE-KEEP-VARIANTS']);
        $variant = Variant::create([
            'product_id' => $product->id,
            'price' => 1.5,
            'is_dollar' => false,
            'stock' => 6,
            'property' => 'محتفظ به',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/admin/products/'.$product->id)
            ->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('variants', ['id' => $variant->id, 'product_id' => null]);
        $this->assertSame(1, Variant::count());
    }

    public function test_admin_can_filter_search_and_toggle_variants(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $regularUser = User::factory()->create(['is_admin' => false]);
        $product = Product::create(['name' => 'مشروب كولا', 'sku_code' => 'VARIANT-API-1']);
        $linkedVariant = Variant::create([
            'product_id' => $product->id,
            'price' => 1,
            'is_dollar' => false,
            'stock' => 4,
            'property' => 'حجم صغير',
            'is_active' => true,
        ]);
        $unlinkedVariant = Variant::create([
            'product_id' => null,
            'bayan_id' => 990,
            'price' => 1.5,
            'is_dollar' => false,
            'stock' => 0,
            'property' => 'كولا زيرو',
            'is_active' => false,
            'bayan_unavailable' => true,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/variants?linked=0&search=زيرو')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unlinkedVariant->id)
            ->assertJsonPath('data.0.product_name', null);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/variants?linked=1&search=كولا')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $linkedVariant->id)
            ->assertJsonPath('data.0.product_name', 'مشروب كولا');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/variants/'.$unlinkedVariant->id.'/toggle-active')
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertFalse($unlinkedVariant->fresh()->bayan_unavailable);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/variants/'.$unlinkedVariant->id.'/toggle-active')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($unlinkedVariant->fresh()->is_auto_deactivated);

        $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/admin/variants')
            ->assertForbidden();
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

    public function test_order_exhaustion_marks_variant_as_automatically_deactivated(): void
    {
        $user = User::factory()->create();
        $product = Product::create(['name' => 'آخر قطعة', 'sku_code' => 'AUTO-DEACTIVATE-ORDER']);
        $variant = Variant::create([
            'product_id' => $product->id,
            'price' => 2,
            'is_dollar' => false,
            'stock' => 1,
            'property' => 'قطعة واحدة',
            'is_active' => true,
        ]);
        Cart::create([
            'user_id' => $user->id,
            'variant_id' => $variant->id,
            'count' => 1,
        ]);

        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('notifictionCreateOrdarForAdmin')->once();
        $notifications->shouldReceive('notifictionCreateOrdarForUser')->once();
        $this->instance(NotificationService::class, $notifications);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/ordar/confirme', ['is_delivery' => false])
            ->assertOk();

        $this->assertSame(0, $variant->fresh()->stock);
        $this->assertFalse((bool) $variant->fresh()->is_active);
        $this->assertTrue($variant->fresh()->is_auto_deactivated);
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
        $this->assertSame(7, $variantUpsertQueries);
    }

    public function test_negative_stock_count_includes_newly_imported_variants(): void
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

        $this->assertSame(1, $result['negative_stock_variants']);
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
