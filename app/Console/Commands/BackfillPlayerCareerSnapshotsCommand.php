<?php

namespace App\Console\Commands;

use App\Services\Career\PlayerCareerBackfillService;
use Illuminate\Console\Command;

class BackfillPlayerCareerSnapshotsCommand extends Command
{
    protected $signature = 'career:backfill-snapshots';

    protected $description = 'Zapisuje snapshoty kariery z istniejących meczów (bez treningu lokalnego)';

    public function handle(PlayerCareerBackfillService $backfillService): int
    {
        $processed = $backfillService->backfillAll();
        $this->info("Przetworzono {$processed} meczów (snapshot tylko gdy są wizyty / lotki).");

        return self::SUCCESS;
    }
}
