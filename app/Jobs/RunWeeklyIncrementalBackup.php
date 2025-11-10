<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RunWeeklyIncrementalBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 minutes

    public function handle(): void
    {
        try {
            Log::info('Starting backup:weekly-incremental command via job.');
            Artisan::call('backup:weekly-incremental');
            Log::info('Completed backup:weekly-incremental command.', ['output' => Artisan::output()]);
        } catch (\Throwable $e) {
            Log::error('backup:weekly-incremental job failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
}