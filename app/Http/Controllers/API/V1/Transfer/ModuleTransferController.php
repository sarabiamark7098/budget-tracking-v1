<?php

namespace App\Http\Controllers\API\V1\Transfer;

use App\Http\Controllers\Controller;
use App\Models\CryptoLot;
use App\Models\Investment;
use App\Models\ModuleTransfer;
use App\Models\StockLot;
use App\Services\DashboardService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleTransferController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $budget    = $this->budget($request);
        $transfers = ModuleTransfer::where('budget_tracking_id', $budget->id)
            ->when($request->module, fn($q, $m) => $q->where('module', $m))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 20);

        return $this->respondSuccess($transfers);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'module'        => ['required', 'in:investment,stock,crypto,saving,income'],
            'transfer_from' => ['required', 'in:income,investment,stock,crypto,saving'],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'transfer_fee'  => ['required', 'numeric', 'min:0'],
            'note'          => ['nullable', 'string', 'max:255'],
            'transfer_date' => ['required', 'date'],
        ]);

        $budget = $this->budget($request);
        $btId   = $budget->id;
        $total  = round($data['amount'] + $data['transfer_fee'], 2);

        // Prevent same-fund transfers
        if ($data['transfer_from'] === $data['module']) {
            return $this->respondError('Cannot transfer to the same fund.', 422);
        }

        // Compute available balance of the source account
        $sourceBalance = match ($data['transfer_from']) {
            'income'     => $budget->availableBalance(),
            'investment' => $this->moduleAvailableBalance($btId, 'investment'),
            'stock'      => $this->moduleAvailableBalance($btId, 'stock'),
            'crypto'     => $this->moduleAvailableBalance($btId, 'crypto'),
            'saving'     => $this->moduleAvailableBalance($btId, 'saving'),
        };

        if ($total > $sourceBalance) {
            return $this->respondError(
                'Insufficient balance in ' . ucfirst($data['transfer_from']) . '. ' .
                'Available: ₱' . number_format($sourceBalance, 2) .
                ', Required: ₱' . number_format($total, 2),
                422
            );
        }

        $data['total']              = $total;
        $data['budget_tracking_id'] = $budget->id;
        $data['user_id']            = auth()->id();

        $transfer = ModuleTransfer::create($data);
        DashboardService::clearAllTimeCache($budget->id);

        return $this->respondCreated($transfer, 'Transfer recorded successfully');
    }

    private function moduleAvailableBalance(int $btId, string $module): float
    {
        // All money transferred INTO this fund (from any source)
        $transferredIn = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('module', $module)
            ->sum('amount');

        // All money transferred OUT of this fund (to any destination, full total incl. fee)
        $transferredOut = (float) ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('transfer_from', $module)
            ->sum('total');

        // Assets already deployed from this fund (cannot be re-transferred)
        $deployed = match ($module) {
            'investment' => (float) Investment::where('budget_tracking_id', $btId)->sum('amount_invested'),
            'stock'      => (float) (StockLot::whereHas('stock', fn($q) => $q->where('budget_tracking_id', $btId))->selectRaw('SUM(shares * buy_price) as total')->value('total') ?? 0),
            'crypto'     => (float) (CryptoLot::whereHas('cryptoAsset', fn($q) => $q->where('budget_tracking_id', $btId))->selectRaw('SUM(quantity * buy_price) as total')->value('total') ?? 0),
            'saving'     => 0, // saving has no deployed assets
            default      => 0,
        };

        return $transferredIn - $transferredOut - $deployed;
    }
}
