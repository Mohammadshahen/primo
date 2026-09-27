<?php

namespace App\Console\Commands;

use App\Services\BayanProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncBayanProducts extends Command
{
    protected $signature = 'bayan:sync-products {--dry-run : Fetch and validate without writing to the database}';

    protected $description = 'Import and synchronize eligible variants from the Bayan accounting API';

    public function handle(BayanProductSyncService $syncService): int
    {
        $lock = Cache::lock('bayan-sync', 3600);
        if (! $lock->get()) {
            $this->warn('Bayan sync is already running. Skipping this run.');

            return self::SUCCESS;
        }

        $startedAt = microtime(true);

        try {
            $result = $syncService->sync((bool) $this->option('dry-run'));
            $duration = round(microtime(true) - $startedAt, 2);

            Log::info('Bayan sync command completed.', [
                'duration_seconds' => $duration,
                'counts' => $result,
            ]);

            if ($duration > 600) {
                Log::warning('Bayan sync took longer than 10 minutes.', [
                    'duration_seconds' => $duration,
                ]);
            }

            if ($result['records_received'] === 0) {
                $this->warn('Bayan API returned no records. No database changes were made.');
            }

            $this->table(
                ['Result', 'Count'],
                collect($result)->map(fn ($count, $label) => [$label, $count])->values()->all()
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Bayan product sync failed.', [
                'exception' => $exception,
                'duration_seconds' => round(microtime(true) - $startedAt, 2),
            ]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
