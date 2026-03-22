<?php

namespace App\Http\Controllers\API\V1\Investment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\StoreInvestmentRequest;
use App\Http\Requests\Investment\UpdateInvestmentRequest;
use App\Http\Resources\Investment\InvestmentResource;
use App\Models\Investment;
use App\Models\InvestmentPayment;
use App\Services\InvestmentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private InvestmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'per_page']);
        $investments = $this->service->getAll($this->budget($request), $filters);
        return $this->respondSuccess(InvestmentResource::collection($investments)->response()->getData(true));
    }

    public function store(StoreInvestmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['purchase_date'])) {
            $data['purchase_date'] = now()->toDateString();
        }
        // For real estate, amount_invested and current_value mirror total_value
        if (($data['type'] ?? '') === 'real_estate' && !empty($data['total_value'])) {
            $data['amount_invested'] = $data['total_value'];
            $data['current_value']   = $data['total_value'];
        }
        if (empty($data['current_value'])) {
            $data['current_value'] = $data['amount_invested'] ?? 0;
        }
        $investment = $this->service->create($this->budget($request), auth()->user(), $data);
        $investment->load('category');
        return $this->respondCreated(new InvestmentResource($investment), 'Investment created successfully');
    }

    public function show(Request $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $investment->load('category');
        return $this->respondSuccess(new InvestmentResource($investment));
    }

    public function update(UpdateInvestmentRequest $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $investment = $this->service->update($investment, $request->validated());
        return $this->respondSuccess(new InvestmentResource($investment), 'Investment updated successfully');
    }

    public function destroy(Request $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $this->service->delete($investment);
        return $this->respondSuccess(null, 'Investment deleted successfully');
    }

    public function portfolio(Request $request): JsonResponse
    {
        $summary = $this->service->getPortfolioSummary($this->budget($request));
        return $this->respondSuccess($summary, 'Portfolio summary retrieved');
    }

    public function getPayments(Request $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $payments  = $investment->payments()->orderBy('payment_date', 'desc')->get();
        $totalPaid = (float) $payments->sum('amount');
        $totalDue  = null;

        if (in_array($investment->type, ['real_estate', 'other'])) {
            $totalDue = round(($investment->months_of_payment ?? 0) * ($investment->amount_per_payment ?? 0), 2);
        }

        return $this->respondSuccess([
            'payments'   => $payments,
            'total_paid' => round($totalPaid, 2),
            'total_due'  => $totalDue,
            'status'     => $investment->payment_status,
        ]);
    }

    public function storePayment(Request $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:255'],
        ]);

        $btId             = $this->budget($request)->id;
        $totalTransferred = (float) \App\Models\ModuleTransfer::where('budget_tracking_id', $btId)
            ->where('module', 'investment')->sum('amount');
        $totalPaid        = (float) InvestmentPayment::whereHas('investment', fn($q) => $q->where('budget_tracking_id', $btId))->sum('amount');
        $available        = round($totalTransferred - $totalPaid, 2);

        if (round((float) $data['amount'], 2) > $available) {
            return $this->respondError(
                'Insufficient investment balance. Available: ₱' . number_format($available, 2) .
                ', Required: ₱' . number_format($data['amount'], 2),
                422
            );
        }

        $payment = InvestmentPayment::create(array_merge($data, ['investment_id' => $investment->id]));

        // Auto-mark as paid for real_estate and other types
        if (in_array($investment->type, ['real_estate', 'other']) && $investment->payment_status === 'active') {
            $totalDue  = ($investment->months_of_payment ?? 0) * ($investment->amount_per_payment ?? 0);
            $totalPaid = $investment->fresh()->total_paid;
            if ($totalDue > 0 && $totalPaid >= $totalDue) {
                $investment->update(['payment_status' => 'paid']);
            }
        }

        $investment->refresh();
        return $this->respondCreated([
            'payment'        => $payment,
            'payment_status' => $investment->payment_status,
            'total_paid'     => $investment->total_paid,
        ], 'Payment recorded successfully');
    }

    public function markDone(Request $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $investment->update(['payment_status' => 'done']);
        return $this->respondSuccess(new InvestmentResource($investment), 'Marked as done');
    }

    // ── Dividends ─────────────────────────────────────────────────────────────

    public function storeDividend(Request $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');

        $data = $request->validate([
            'amount'  => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'notes'   => ['nullable', 'string', 'max:255'],
        ]);

        $data['paid_at'] = $data['paid_at'] ?? now()->toDateString();

        $dividend = $this->service->recordDividend($investment, $data);
        $summary  = $this->service->getPortfolioSummary($this->budget($request));

        return $this->respondCreated([
            'dividend'  => $dividend,
            'portfolio' => $summary,
        ], 'Dividend recorded. Amount added to available balance.');
    }

    public function getDividends(Request $request, Investment $investment): JsonResponse
    {
        abort_if($investment->budget_tracking_id !== $this->budget($request)->id, 403, 'Unauthorized');
        $dividends = $this->service->getDividends($investment);
        return $this->respondSuccess(['dividends' => $dividends]);
    }
}
