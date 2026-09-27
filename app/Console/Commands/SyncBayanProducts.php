<?php

namespace App\Console\Commands;

use App\Services\BayanProductSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncBayanProducts extends Command
{
    protected $signature = 'bayan:sync-products {--dry-run : Fetch and validate without writing to the database}';

    protected $description = 'Update manually linked variants from the Bayan accounting API';

    public function handle(BayanProductSyncService $syncService): int
    {
        try {
            $result = $syncService->sync((bool) $this->option('dry-run'));
        } catch (Throwable $exception) {
            Log::error('Bayan product sync failed.', ['exception' => $exception]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result['records_received'] === 0) {
            $this->warn('Bayan API returned no records. No database changes were made.');
        }

        $this->table(
            ['Result', 'Count'],
            collect($result)->map(fn ($count, $label) => [$label, $count])->values()->all()
        );

        return self::SUCCESS;
    }
}
