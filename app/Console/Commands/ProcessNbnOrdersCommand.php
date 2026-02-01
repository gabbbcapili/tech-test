<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnOrderJob;
use App\Models\Application;
use Illuminate\Console\Command;

class ProcessNbnOrdersCommand extends Command {
    protected $signature = 'applications:process-nbn-orders';

    protected $description = 'Dispatch jobs to process all NBN applications with order status';

    public function handle(): int {
        Application::query()
            ->where('status', ApplicationStatus::Order->value)
            ->whereHas('plan', fn($q) => $q->where('type', 'nbn'))
            ->orderBy('id')
            ->chunkById(100, function ($applications) {
                foreach ($applications as $application) {
                    ProcessNbnOrderJob::dispatch($application);
                }
            });

        $this->info('Dispatched jobs for NBN applications with order status.');

        return self::SUCCESS;
    }
}
