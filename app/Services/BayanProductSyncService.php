<?php

namespace App\Services;

use App\Models\Variant;
use Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BayanProductSyncService
{
    private const DB_CHUNK_SIZE = 500;

    private const MYSQL_UPSERT_CHUNK_SIZE = 500;

    private const SQLITE_UPSERT_CHUNK_SIZE = 80;

    public function sync(bool $dryRun = false): array
    {
        $priceField = config('services.bayan.price_field', 'Price4');
        if (! in_array($priceField, ['Price1', 'Price2', 'Price3', 'Price4', 'Price5'], true)) {
            throw new RuntimeException('BAYAN_PRICE_FIELD must be one of Price1 through Price5.');
        }

        [$sourceVariants, $recordsReceived] = $this->loadSourceVariants($priceField);
        if ($recordsReceived === 0 || $sourceVariants === []) {
            throw new RuntimeException('Bayan API returned no variant records; no variants were changed.');
        }

        $counts = [
            'variants_created' => 0,
            'variants_updated' => 0,
            'variants_deactivated' => 0,
            'negative_stock_variants' => 0,
            'linked_variants' => 0,
        ];

        if ($dryRun) {
            DB::table('variants')
                ->whereNotNull('bayan_id')
                ->select(['id', 'bayan_id', 'is_active'])
                ->chunkById(self::DB_CHUNK_SIZE, function ($linkedVariants) use ($sourceVariants, &$counts): void {
                    foreach ($linkedVariants as $variant) {
                        $counts['linked_variants']++;
                        $bayanId = (int) $variant->bayan_id;
                        if (isset($sourceVariants[$bayanId])) {
                            $counts['variants_updated']++;
                        } elseif ($variant->is_active) {
                            $counts['variants_deactivated']++;
                        }
                    }
                });

            foreach (array_chunk(array_keys($sourceVariants), $this->upsertChunkSize()) as $sourceIds) {
                $existingCount = DB::table('variants')->whereIn('bayan_id', $sourceIds)->count();
                $counts['variants_created'] += count($sourceIds) - $existingCount;
            }

            return $counts + [
                'records_received' => $recordsReceived,
                'source_variants' => count($sourceVariants),
                'dry_run' => true,
            ];
        }

        DB::transaction(function () use ($sourceVariants, $priceField, &$counts): void {
            $this->applySourceSnapshot($sourceVariants, $priceField, $counts);
            $this->syncProductAvailability();
        });

        if ($counts['negative_stock_variants'] > 0) {
            Log::warning('Bayan variants have negative stock; stored stock was clamped to zero.', [
                'count' => $counts['negative_stock_variants'],
            ]);
        }

        $result = $counts + [
            'records_received' => $recordsReceived,
            'source_variants' => count($sourceVariants),
            'dry_run' => false,
        ];
        Log::info('Bayan variant sync completed.', $result);

        return $result;
    }

    public function unlinkedVariants(): array
    {
        $priceField = config('services.bayan.price_field', 'Price4');
        if (! in_array($priceField, ['Price1', 'Price2', 'Price3', 'Price4', 'Price5'], true)) {
            throw new RuntimeException('BAYAN_PRICE_FIELD must be one of Price1 through Price5.');
        }

        [$records, $recordsReceived] = $this->loadSourceVariants($priceField);
        if ($recordsReceived === 0) {
            throw new RuntimeException('Bayan API returned an empty snapshot.');
        }

        $linkedIds = array_fill_keys(
            Variant::whereNotNull('bayan_id')->pluck('bayan_id')->map(fn ($id) => (int) $id)->all(),
            true
        );
        $unlinked = [];

        foreach ($records as $id => $record) {
            if (isset($linkedIds[$id])) {
                continue;
            }

            $unlinked[] = [
                'bayan_id' => $id,
                'bayan_variant_key' => $record['bayan_variant_key'],
                'name' => $record['Name'],
                'property' => $record['Name'],
                'price' => $record[$priceField],
                'stock' => max(0, $record['Quantity']),
                'is_dollar' => $record['CURRENCY'] === 2,
                'bayan_currency_id' => $record['CURRENCY'],
            ];
        }

        return $unlinked;
    }

    private function applySourceSnapshot(array $sourceVariants, string $priceField, array &$counts): void
    {
        DB::table('variants')
            ->whereNotNull('bayan_id')
            ->select([
                'id',
                'bayan_id',
                'is_active',
            ])
            ->chunkById(self::DB_CHUNK_SIZE, function ($linkedVariants) use ($sourceVariants, &$counts): void {
                $deactivateIds = [];
                $timestamp = now();

                foreach ($linkedVariants as $variant) {
                    $counts['linked_variants']++;
                    $bayanId = (int) $variant->bayan_id;
                    if (! isset($sourceVariants[$bayanId]) && $variant->is_active) {
                        $deactivateIds[] = $variant->id;
                        $counts['variants_deactivated']++;
                    }
                }

                if ($deactivateIds !== []) {
                    Variant::whereIn('id', $deactivateIds)->update([
                        'is_active' => false,
                        'bayan_unavailable' => true,
                        'is_auto_deactivated' => true,
                        'updated_at' => $timestamp,
                    ]);
                }
            });

        foreach (array_chunk($sourceVariants, $this->upsertChunkSize(), true) as $sourceChunk) {
            $existingVariants = DB::table('variants')
                ->whereIn('bayan_id', array_keys($sourceChunk))
                ->get([
                    'bayan_id',
                    'price',
                    'stock',
                    'is_dollar',
                    'bayan_currency_id',
                    'property',
                    'bayan_variant_key',
                    'is_active',
                    'bayan_unavailable',
                    'is_auto_deactivated',
                ])
                ->keyBy('bayan_id');
            $upserts = [];
            $timestamp = now();

            foreach ($sourceChunk as $bayanId => $record) {
                $existing = $existingVariants->get($bayanId);
                $sourceStock = $record['Quantity'];
                $newPrice = round($record[$priceField], 3);
                $newStock = max(0, $sourceStock);
                $newIsDollar = $record['CURRENCY'] === 2;
                $newCurrencyId = $record['CURRENCY'];
                $newProperty = $record['Name'];
                $newVariantKey = $record['bayan_variant_key'];
                $newIsActive = $newStock > 0 && (
                    $existing === null
                    || (bool) $existing->is_active
                    || (bool) $existing->is_auto_deactivated
                );
                $newBayanUnavailable = $newStock === 0;
                $newIsAutoDeactivated = $newStock === 0
                    || ($existing === null || (bool) $existing->is_auto_deactivated);

                if ($existing !== null) {
                    $hasChanges = number_format((float) $existing->price, 3, '.', '')
                        !== number_format($newPrice, 3, '.', '')
                    || (int) $existing->stock !== $newStock
                    || (bool) $existing->is_dollar !== $newIsDollar
                    || (int) $existing->bayan_currency_id !== $newCurrencyId
                    || $existing->property !== $newProperty
                    || $existing->bayan_variant_key !== $newVariantKey
                    || (bool) $existing->is_active !== $newIsActive
                    || (bool) $existing->bayan_unavailable !== $newBayanUnavailable
                    || (bool) $existing->is_auto_deactivated !== $newIsAutoDeactivated;

                    if (! $hasChanges) {
                        continue;
                    }

                    if ($existing->is_active && ! $newIsActive) {
                        $counts['variants_deactivated']++;
                    }
                    $counts['variants_updated']++;
                } else {
                    $counts['variants_created']++;
                }

                $upserts[] = [
                    'bayan_id' => $bayanId,
                    'bayan_variant_key' => $newVariantKey,
                    'bayan_currency_id' => $newCurrencyId,
                    'price' => $newPrice,
                    'stock' => $newStock,
                    'is_dollar' => $newIsDollar,
                    'property' => $newProperty,
                    'is_active' => $newIsActive,
                    'bayan_unavailable' => $newBayanUnavailable,
                    'is_auto_deactivated' => $newIsAutoDeactivated,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
                $counts['negative_stock_variants'] += $sourceStock < 0 ? 1 : 0;
            }

            if ($upserts !== []) {
                Variant::upsert($upserts, ['bayan_id'], [
                    'price',
                    'stock',
                    'is_dollar',
                    'bayan_currency_id',
                    'property',
                    'bayan_variant_key',
                    'is_active',
                    'bayan_unavailable',
                    'is_auto_deactivated',
                    'updated_at',
                ]);
            }
        }
    }

    private function syncProductAvailability(): void
    {
        $timestamp = now();

        DB::table('products')
            ->where('bayan_auto_disabled', true)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('variants')
                    ->whereColumn('variants.product_id', 'products.id')
                    ->where('variants.is_active', true);
            })
            ->update([
                'is_active' => true,
                'bayan_auto_disabled' => false,
                'updated_at' => $timestamp,
            ]);

        DB::table('products')
            ->where('is_active', true)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('variants')
                    ->whereColumn('variants.product_id', 'products.id')
                    ->where('variants.is_active', true);
            })
            ->update([
                'is_active' => false,
                'bayan_auto_disabled' => true,
                'updated_at' => $timestamp,
            ]);
    }

    private function fetchRecords(): Generator
    {
        $url = config('services.bayan.products_url');
        $pageSize = (int) config('services.bayan.page_size', 200);
        $timeout = (int) config('services.bayan.timeout', 10);
        $retries = max(1, (int) config('services.bayan.retry', 2));
        $retryDelay = max(0, (int) config('services.bayan.retry_delay', 500));

        if (! $url || $pageSize < 1 || $pageSize > 1000) {
            throw new RuntimeException('Configure BAYAN_PRODUCTS_URL and a BAYAN_PAGE_SIZE between 1 and 1000.');
        }

        $request = Http::acceptJson()
            ->timeout($timeout)
            ->retry($retries, $retryDelay, fn ($exception) => $exception instanceof ConnectionException
                || ($exception instanceof RequestException && $exception->response->serverError()));
        if ($token = config('services.bayan.token')) {
            $request = $request->withToken($token);
        }

        $offset = 0;
        $lastFingerprint = null;
        do {
            $response = $request->get($url, ['offset' => $offset, 'limet' => $pageSize]);
            $response->throw();
            $page = $this->normalizeResponse($response->json());

            if ($page === []) {
                break;
            }

            $fingerprint = crc32(serialize($page));
            if ($fingerprint === $lastFingerprint) {
                throw new RuntimeException('Bayan API returned a repeated page; refusing an incomplete sync.');
            }
            $lastFingerprint = $fingerprint;

            $pageCount = count($page);
            foreach ($page as $record) {
                yield $record;
            }
            $offset += $pageCount;
        } while ($pageCount === $pageSize);
    }

    private function normalizeResponse(mixed $payload): array
    {
        if (! is_array($payload)) {
            throw new RuntimeException('Bayan API response is not a JSON array or object.');
        }

        if ($payload === []) {
            return [];
        }

        foreach (['data', 'products'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $payload = $payload[$key];
                break;
            }
        }

        if (isset($payload['Id'])) {
            return [$payload];
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        $values = array_values($payload);
        if ($values !== [] && is_array($values[0])) {
            return $values;
        }

        throw new RuntimeException('Bayan API response does not contain product records.');
    }

    private function loadSourceVariants(string $priceField): array
    {
        $variants = [];
        $recordsReceived = 0;

        foreach ($this->fetchRecords() as $record) {
            $recordsReceived++;
            if (! is_array($record)
                || ! isset($record['Id'], $record['Name'], $record['Kind'])
                || ! $this->isIntegerValue($record['Id'])
                || trim((string) $record['Name']) === ''
                || ! $this->isIntegerValue($record['Kind'])
                || ! in_array((int) $record['Kind'], [0, 1], true)) {
                throw new RuntimeException('Bayan API returned a record with missing or invalid Id, Name, or Kind.');
            }

            if ((int) $record['Kind'] !== 0) {
                continue;
            }

            foreach (['Quantity', 'CURRENCY'] as $field) {
                if (! isset($record[$field]) || ! $this->isIntegerValue($record[$field])) {
                    throw new RuntimeException("Bayan variant {$record['Id']} has no valid {$field} value.");
                }
            }

            if (! isset($record[$priceField]) || ! is_numeric($record[$priceField])) {
                throw new RuntimeException("Bayan variant {$record['Id']} has no valid {$priceField} value.");
            }

            $id = (int) $record['Id'];
            if (isset($variants[$id])) {
                throw new RuntimeException("Bayan API returned duplicate variant ID {$id}.");
            }

            $name = trim((string) $record['Name']);
            $quantity = (int) $record['Quantity'];
            $variants[$id] = [
                'Name' => $name,
                'Quantity' => $quantity,
                'CURRENCY' => (int) $record['CURRENCY'],
                $priceField => (float) $record[$priceField],
                'bayan_variant_key' => $this->variantKey($id, $name),
            ];
        }

        return [$variants, $recordsReceived];
    }

    private function variantKey(int $id, string $name): string
    {
        return hash('sha256', (string) $id."\0".$name);
    }

    private function isIntegerValue(mixed $value): bool
    {
        if (is_int($value)) {
            return true;
        }

        if (! is_string($value) || $value === '') {
            return false;
        }

        $digits = str_starts_with($value, '-') ? substr($value, 1) : $value;

        return $digits !== '' && ctype_digit($digits);
    }

    private function upsertChunkSize(): int
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? self::SQLITE_UPSERT_CHUNK_SIZE
            : self::MYSQL_UPSERT_CHUNK_SIZE;
    }
}
