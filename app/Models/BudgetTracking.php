<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

// Module models
use App\Models\Category;
use App\Models\Income;
use App\Models\Expense;
use App\Models\Budget;
use App\Models\Debt;
use App\Models\Payment;
use App\Models\Investment;
use App\Models\Stock;
use App\Models\CryptoAsset;
use App\Models\FinancialPlan;
use App\Models\FinancialGoal;
use App\Models\InsurancePlan;
use App\Models\InsurancePayment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\MP2Plan;
use App\Models\ModuleTransfer;

class BudgetTracking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'currency',
        'period',
        'start_date',
        'join_code',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    protected $appends = ['total_allocated', 'total_income', 'total_expense', 'balance', 'available_balance'];

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(BudgetTrackingMember::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BudgetTrackingAllocation::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BudgetTrackingTransaction::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BudgetTrackingHistory::class)->orderByDesc('created_at');
    }

    // ─── Module Relationships (all data scoped to this tracker) ─────────────────

    public function categories(): HasMany    { return $this->hasMany(Category::class); }
    public function incomes(): HasMany       { return $this->hasMany(Income::class); }
    public function expenses(): HasMany      { return $this->hasMany(Expense::class); }
    public function budgets(): HasMany       { return $this->hasMany(Budget::class); }
    public function debts(): HasMany         { return $this->hasMany(Debt::class); }
    public function payments(): HasMany      { return $this->hasMany(Payment::class); }
    public function investments(): HasMany   { return $this->hasMany(Investment::class); }
    public function stocks(): HasMany        { return $this->hasMany(Stock::class); }
    public function cryptoAssets(): HasMany  { return $this->hasMany(CryptoAsset::class); }
    public function financialPlans(): HasMany{ return $this->hasMany(FinancialPlan::class); }
    public function financialGoals(): HasMany{ return $this->hasMany(FinancialGoal::class); }
    public function insurancePlans(): HasMany{ return $this->hasMany(InsurancePlan::class); }
    public function insurancePayments(): HasMany { return $this->hasMany(InsurancePayment::class); }
    public function purchases(): HasMany        { return $this->hasMany(Purchase::class); }
    public function purchasePayments(): HasMany { return $this->hasMany(PurchasePayment::class); }
    public function mp2Plans(): HasMany           { return $this->hasMany(MP2Plan::class); }
    public function moduleTransfers(): HasMany    { return $this->hasMany(ModuleTransfer::class); }

    // ─── Computed Attributes ────────────────────────────────────────────────────

    public function getTotalAllocatedAttribute(): float
    {
        return (float) $this->allocations()->sum('allocated_amount');
    }

    public function getTotalIncomeAttribute(): float
    {
        return (float) $this->transactions()->where('type', 'income')->sum('amount');
    }

    public function getTotalExpenseAttribute(): float
    {
        return (float) $this->transactions()->where('type', 'expense')->sum('amount');
    }

    public function getBalanceAttribute(): float
    {
        return $this->total_income - $this->total_expense;
    }

    // ─── Balance ─────────────────────────────────────────────────────────────────

    /**
     * Current available balance = total income − total expenses − total debt payments.
     *
     * Pass $creditBack to temporarily add back the amount of a record being edited,
     * so the check produces "how much room do we have after reverting that record".
     *
     *   $available = $tracker->availableBalance(creditBack: $expense->amount);
     */
    public function availableBalance(float $creditBack = 0.0): float
    {
        $income = (float) $this->incomes()->sum('amount');
        $expenses = (float) $this->expenses()->sum('amount');

        // Personal debt payments are OUTGOING (deducted from income)
        $personalPayments = (float) $this->payments()
            ->whereHas('debt', fn($q) => $q->where('type', 'personal'))
            ->sum('amount');

        // Business debt payments are INCOMING (added to income — you are the lender receiving back money)
        $businessReceived = (float) $this->payments()
            ->whereHas('debt', fn($q) => $q->where('type', 'business'))
            ->sum('amount');

        $purchasePayments    = (float) $this->purchasePayments()->sum('amount');
        // Deduct ALL outgoing transfers to any fund (investment, stock, crypto, saving)
        $moduleTransfersOut  = (float) $this->moduleTransfers()->whereIn('module', ['investment','stock','crypto','saving'])->sum('total');
        // Transfers from a module back to income — credit back to the income pool
        $moduleTransfersBack = (float) $this->moduleTransfers()->where('module', 'income')->sum('amount');

        return $income - $expenses - $personalPayments + $businessReceived - $purchasePayments - $moduleTransfersOut + $moduleTransfersBack + $creditBack;
    }

    public function getAvailableBalanceAttribute(): float
    {
        return $this->availableBalance();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    public function isMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function isOwner(int $userId): bool
    {
        return $this->owner_id === $userId;
    }

    /**
     * Generate a fresh unique 8-character uppercase alphanumeric join code.
     */
    public static function generateJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('join_code', $code)->exists());

        return $code;
    }
}
