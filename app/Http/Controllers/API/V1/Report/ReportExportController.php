<?php

namespace App\Http\Controllers\API\V1\Report;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportExportJob;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportExportController extends Controller
{
    use ApiResponseTrait;

    /**
     * POST /api/v1/reports/export/queue
     * Dispatch a background job to build the export, return an export ID.
     */
    public function queue(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to']);

        $exportId = (string) Str::uuid();

        Cache::put("export:{$exportId}:status", 'pending', now()->addHour());

        GenerateReportExportJob::dispatch(
            $this->budget($request)->id,
            $filters,
            $exportId,
        );

        return $this->respondSuccess(['export_id' => $exportId], 'Export queued');
    }

    /**
     * GET /api/v1/reports/export/{exportId}/status
     * Poll the status of a queued export.
     */
    public function status(string $exportId): JsonResponse
    {
        $status = Cache::get("export:{$exportId}:status");

        if ($status === null) {
            return $this->respondError('Export not found or expired', 404);
        }

        $payload = ['export_id' => $exportId, 'status' => $status];

        if ($status === 'failed') {
            $payload['error'] = Cache::get("export:{$exportId}:error");
        }

        return $this->respondSuccess($payload);
    }

    /**
     * GET /api/v1/reports/export/{exportId}/download
     * Download the completed export file.
     */
    public function download(string $exportId)
    {
        $status = Cache::get("export:{$exportId}:status");

        if ($status !== 'done') {
            return response()->json([
                'success' => false,
                'message' => match(true) {
                    $status === null       => 'Export not found or expired.',
                    $status === 'failed'   => 'Export failed: ' . Cache::get("export:{$exportId}:error"),
                    default                => 'Export is not ready yet (status: ' . $status . ').',
                },
            ], 422);
        }

        $path = Cache::get("export:{$exportId}:path");

        if (!$path || !Storage::disk('local')->exists($path)) {
            return response()->json(['success' => false, 'message' => 'Export file not found.'], 404);
        }

        $filename = 'full_report_' . now()->format('Y_m_d') . '.xlsx';

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
