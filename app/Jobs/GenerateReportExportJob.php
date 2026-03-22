<?php

namespace App\Jobs;

use App\Models\BudgetTracking;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GenerateReportExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(
        private readonly int    $budgetTrackingId,
        private readonly array  $filters,
        private readonly string $exportId,
    ) {}

    public function handle(ReportService $service): void
    {
        Cache::put("export:{$this->exportId}:status", 'processing', now()->addHour());

        try {
            $budget = BudgetTracking::findOrFail($this->budgetTrackingId);

            $relativePath = "exports/{$this->exportId}.xlsx";
            $absolutePath = Storage::disk('local')->path($relativePath);

            // Ensure the exports directory exists.
            Storage::disk('local')->makeDirectory('exports');

            $service->saveToFile($budget, $this->filters, $absolutePath);

            Cache::put("export:{$this->exportId}:status", 'done', now()->addHour());
            Cache::put("export:{$this->exportId}:path",   $relativePath, now()->addHour());
        } catch (\Throwable $e) {
            Cache::put("export:{$this->exportId}:status", 'failed', now()->addHour());
            Cache::put("export:{$this->exportId}:error",  $e->getMessage(), now()->addHour());
            throw $e;
        }
    }
}
