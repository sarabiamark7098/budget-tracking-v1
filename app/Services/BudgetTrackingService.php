<?php

namespace App\Services;

use App\Models\BudgetTracking;
use App\Models\BudgetTrackingAllocation;
use App\Models\BudgetTrackingHistory;
use App\Models\BudgetTrackingMember;
use App\Models\BudgetTrackingTransaction;
use App\Models\CryptoAsset;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Investment;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetTrackingService
{
    // ─── Budget Tracking CRUD ────────────────────────────────────────────────────

    /**
     * Get the user's single budget tracking (owned or shared).
     * Returns null if the user has no membership or their tracker is archived.
     */
    public function getForUser(User $user): ?BudgetTracking
    {
        $membership = BudgetTrackingMember::where('user_id', $user->id)->first();

        if (! $membership) {
            return null;
        }

        $budget = BudgetTracking::with([
            'owner',
            'members.user',
            'allocations',
        ])->find($membership->budget_tracking_id);

        // Treat archived trackers as non-existent for active-use purposes
        if (! $budget || $budget->status === 'archived') {
            return null;
        }

        return $budget;
    }

    /**
     * Create a new budget tracking for the user.
     * A user may only have ONE budget tracking total (owned or shared).
     *
     * @throws ValidationException
     */
    public function create(User $user, array $data): BudgetTracking
    {
        if ($user->hasBudgetTracking()) {
            throw ValidationException::withMessages([
                'budget_tracking' => ['You already belong to a budget tracking. Leave or delete it before creating a new one.'],
            ]);
        }

        return DB::transaction(function () use ($user, $data) {
            $budget = BudgetTracking::create(array_merge($data, [
                'owner_id'  => $user->id,
                'join_code' => BudgetTracking::generateJoinCode(),
                'status'    => 'active',
            ]));

            BudgetTrackingMember::create([
                'budget_tracking_id' => $budget->id,
                'user_id'            => $user->id,
                'role'               => 'owner',
                'joined_at'          => now(),
            ]);

            // Seed default categories scoped to this tracker
            app(CategoryService::class)->seedDefaultCategories($budget, $user);

            $this->log($budget, $user, 'budget_created', 'budget_tracking', $budget->id, null, $budget->toArray(), "Budget tracking \"{$budget->name}\" created.");

            return $budget->load(['owner', 'members.user', 'allocations']);
        });
    }

    /**
     * Update budget tracking settings. Only the owner may update.
     *
     * @throws ValidationException
     */
    public function update(BudgetTracking $budget, User $user, array $data): BudgetTracking
    {
        $this->assertOwner($budget, $user);

        $old = $budget->only(['name', 'description', 'currency', 'period', 'start_date', 'status']);
        $budget->update($data);
        $new = $budget->fresh()->only(['name', 'description', 'currency', 'period', 'start_date', 'status']);

        $this->log($budget, $user, 'budget_updated', 'budget_tracking', $budget->id, $old, $new, "Budget tracking \"{$budget->name}\" updated.");

        return $budget->fresh(['owner', 'members.user', 'allocations']);
    }

    /**
     * Delete the budget tracking entirely. Only the owner may delete.
     *
     * @throws ValidationException
     */
    public function delete(BudgetTracking $budget, User $user): bool
    {
        $this->assertOwner($budget, $user);
        return $budget->delete();
    }

    /**
     * Archive the budget tracking. Only the owner may archive.
     * Archives the tracker (preserves all data) and removes all memberships
     * so every member can create or join a new tracker.
     *
     * @throws ValidationException
     */
    public function archive(BudgetTracking $budget, User $user): BudgetTracking
    {
        $this->assertOwner($budget, $user);

        $budget->update(['status' => 'archived']);

        // Remove all memberships so members can join/create a new tracker
        BudgetTrackingMember::where('budget_tracking_id', $budget->id)->delete();

        $this->log($budget, $user, 'budget_archived', 'budget_tracking', $budget->id, ['status' => 'active'], ['status' => 'archived'], "Budget tracking \"{$budget->name}\" archived.");

        return $budget->fresh();
    }

    /**
     * Regenerate the join code. Only the owner may do this.
     *
     * @throws ValidationException
     */
    public function regenerateCode(BudgetTracking $budget, User $user): BudgetTracking
    {
        $this->assertOwner($budget, $user);

        $oldCode = $budget->join_code;
        $newCode = BudgetTracking::generateJoinCode();
        $budget->update(['join_code' => $newCode]);

        $this->log($budget, $user, 'code_regenerated', 'budget_tracking', $budget->id, ['join_code' => $oldCode], ['join_code' => $newCode], 'Join code regenerated.');

        return $budget->fresh();
    }

    // ─── Member Management ───────────────────────────────────────────────────────

    /**
     * Join a budget tracking via its join code.
     * A user may only belong to ONE budget tracking at a time.
     *
     * @throws ValidationException
     */
    public function joinByCode(User $user, string $code): BudgetTracking
    {
        if ($user->hasBudgetTracking()) {
            throw ValidationException::withMessages([
                'join_code' => ['You already belong to a budget tracking. Leave or delete it before joining another.'],
            ]);
        }

        $budget = BudgetTracking::where('join_code', strtoupper(trim($code)))
            ->where('status', 'active')
            ->firstOrFail();

        // Prevent owner from re-joining their own budget (shouldn't happen, but safety check)
        if ($budget->isMember($user->id)) {
            throw ValidationException::withMessages([
                'join_code' => ['You are already a member of this budget tracking.'],
            ]);
        }

        DB::transaction(function () use ($budget, $user) {
            BudgetTrackingMember::create([
                'budget_tracking_id' => $budget->id,
                'user_id'            => $user->id,
                'role'               => 'member',
                'joined_at'          => now(),
            ]);

            $this->log($budget, $user, 'member_joined', 'member', $user->id, null, ['user_id' => $user->id, 'name' => $user->name], "{$user->name} joined the budget tracking.");
        });

        return $budget->load(['owner', 'members.user', 'allocations']);
    }

    /**
     * Leave a budget tracking. Members only — owners must delete instead.
     *
     * @throws ValidationException
     */
    public function leave(BudgetTracking $budget, User $user): void
    {
        if ($budget->isOwner($user->id)) {
            throw ValidationException::withMessages([
                'budget_tracking' => ['Owner cannot leave. Transfer ownership or delete the budget tracking.'],
            ]);
        }

        $member = BudgetTrackingMember::where('budget_tracking_id', $budget->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $member->delete();

        $this->log($budget, $user, 'member_left', 'member', $user->id, ['user_id' => $user->id, 'name' => $user->name], null, "{$user->name} left the budget tracking.");
    }

    /**
     * Remove a member from the budget tracking. Only the owner may do this.
     * The owner cannot remove themselves.
     *
     * @throws ValidationException
     */
    public function removeMember(BudgetTracking $budget, User $owner, int $targetUserId): void
    {
        $this->assertOwner($budget, $owner);

        if ($targetUserId === $owner->id) {
            throw ValidationException::withMessages([
                'user_id' => ['Owner cannot remove themselves. Delete the budget tracking instead.'],
            ]);
        }

        $member = BudgetTrackingMember::where('budget_tracking_id', $budget->id)
            ->where('user_id', $targetUserId)
            ->firstOrFail();

        $removedUser = $member->user;
        $member->delete();

        $this->log($budget, $owner, 'member_removed', 'member', $targetUserId, ['user_id' => $targetUserId, 'name' => $removedUser->name], null, "{$owner->name} removed {$removedUser->name} from the budget tracking.");
    }

    // ─── Allocations ─────────────────────────────────────────────────────────────

    /**
     * Get paginated allocations for a budget tracking.
     */
    public function getAllocations(BudgetTracking $budget): \Illuminate\Database\Eloquent\Collection
    {
        return $budget->allocations()->with('category')->get();
    }

    /**
     * Add a budget allocation (owner only).
     *
     * @throws ValidationException
     */
    public function addAllocation(BudgetTracking $budget, User $user, array $data): BudgetTrackingAllocation
    {
        $this->assertOwner($budget, $user);

        $allocation = BudgetTrackingAllocation::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
        ]));

        $this->log($budget, $user, 'allocation_added', 'allocation', $allocation->id, null, $allocation->toArray(), "Allocation \"{$allocation->name}\" (₱" . number_format($allocation->allocated_amount, 2) . ') added.');

        return $allocation->load('category');
    }

    /**
     * Update an allocation (owner only).
     *
     * @throws ValidationException
     */
    public function updateAllocation(BudgetTracking $budget, User $user, BudgetTrackingAllocation $allocation, array $data): BudgetTrackingAllocation
    {
        $this->assertOwner($budget, $user);

        $old = $allocation->only(['name', 'allocated_amount', 'color', 'icon']);
        $allocation->update($data);
        $new = $allocation->fresh()->only(['name', 'allocated_amount', 'color', 'icon']);

        $this->log($budget, $user, 'allocation_updated', 'allocation', $allocation->id, $old, $new, "Allocation \"{$allocation->name}\" updated.");

        return $allocation->fresh('category');
    }

    /**
     * Delete an allocation (owner only).
     *
     * @throws ValidationException
     */
    public function deleteAllocation(BudgetTracking $budget, User $user, BudgetTrackingAllocation $allocation): bool
    {
        $this->assertOwner($budget, $user);

        $this->log($budget, $user, 'allocation_deleted', 'allocation', $allocation->id, $allocation->toArray(), null, "Allocation \"{$allocation->name}\" deleted.");

        return $allocation->delete();
    }

    // ─── Transactions ────────────────────────────────────────────────────────────

    /**
     * List paginated transactions with optional filters.
     */
    public function getTransactions(BudgetTracking $budget, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return BudgetTrackingTransaction::with(['user', 'category', 'allocation'])
            ->where('budget_tracking_id', $budget->id)
            ->when(! empty($filters['type']),    fn($q) => $q->where('type', $filters['type']))
            ->when(! empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->when(! empty($filters['date_from']), fn($q) => $q->where('date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']),   fn($q) => $q->where('date', '<=', $filters['date_to']))
            ->orderBy('date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Add a transaction. Any member can add.
     */
    public function addTransaction(BudgetTracking $budget, User $user, array $data): BudgetTrackingTransaction
    {
        $tx = BudgetTrackingTransaction::create(array_merge($data, [
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
        ]));

        $typeLabel = ucfirst($tx->type);
        $this->log($budget, $user, 'transaction_added', 'transaction', $tx->id, null, $tx->toArray(), "{$user->name} added {$typeLabel} \"{$tx->title}\" ₱" . number_format($tx->amount, 2) . '.');

        return $tx->load(['user', 'category', 'allocation']);
    }

    /**
     * Update a transaction.
     * Members can only update their own transactions; owner can update any.
     *
     * @throws ValidationException
     */
    public function updateTransaction(BudgetTracking $budget, User $user, BudgetTrackingTransaction $tx, array $data): BudgetTrackingTransaction
    {
        if (! $budget->isOwner($user->id) && $tx->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'transaction' => ['You can only edit your own transactions.'],
            ]);
        }

        $old = $tx->only(['type', 'title', 'amount', 'description', 'date']);
        $tx->update($data);
        $new = $tx->fresh()->only(['type', 'title', 'amount', 'description', 'date']);

        $this->log($budget, $user, 'transaction_updated', 'transaction', $tx->id, $old, $new, "{$user->name} updated transaction \"{$tx->title}\".");

        return $tx->fresh(['user', 'category', 'allocation']);
    }

    /**
     * Delete a transaction.
     * Members can only delete their own; owner can delete any.
     *
     * @throws ValidationException
     */
    public function deleteTransaction(BudgetTracking $budget, User $user, BudgetTrackingTransaction $tx): bool
    {
        if (! $budget->isOwner($user->id) && $tx->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'transaction' => ['You can only delete your own transactions.'],
            ]);
        }

        $this->log($budget, $user, 'transaction_deleted', 'transaction', $tx->id, $tx->toArray(), null, "{$user->name} deleted transaction \"{$tx->title}\" ₱" . number_format($tx->amount, 2) . '.');

        return $tx->delete();
    }

    // ─── Summary ─────────────────────────────────────────────────────────────────

    /**
     * Compute a full summary of the budget tracking:
     * totals, per-allocation usage, per-member contributions.
     */
    public function getSummary(BudgetTracking $budget): array
    {
        $transactions = $budget->transactions()->with(['user', 'category', 'allocation'])->get();
        $allocations  = $budget->allocations()->with('category')->get();

        $totalIncome  = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');
        $totalAllocated = $allocations->sum('allocated_amount');
        $balance      = $totalIncome - $totalExpense;

        // Per-allocation spending breakdown
        $allocationSummary = $allocations->map(function ($a) use ($transactions) {
            $spent = $transactions
                ->where('type', 'expense')
                ->where('budget_tracking_allocation_id', $a->id)
                ->sum('amount');

            return [
                'id'               => $a->id,
                'name'             => $a->name,
                'color'            => $a->color,
                'allocated_amount' => (float) $a->allocated_amount,
                'spent_amount'     => round((float) $spent, 2),
                'remaining_amount' => round(max(0, (float) $a->allocated_amount - $spent), 2),
                'usage_pct'        => (float) $a->allocated_amount > 0
                    ? round(($spent / (float) $a->allocated_amount) * 100, 2)
                    : 0,
            ];
        });

        // Per-member contribution breakdown
        $memberSummary = $budget->members()->with('user')->get()->map(function ($m) use ($transactions) {
            $memberTx = $transactions->where('user_id', $m->user_id);

            return [
                'user_id'        => $m->user_id,
                'name'           => $m->user->name,
                'role'           => $m->role,
                'joined_at'      => $m->joined_at,
                'total_income'   => round((float) $memberTx->where('type', 'income')->sum('amount'), 2),
                'total_expense'  => round((float) $memberTx->where('type', 'expense')->sum('amount'), 2),
                'tx_count'       => $memberTx->count(),
            ];
        });

        // Unallocated expenses (no allocation_id set)
        $unallocatedExpense = $transactions
            ->where('type', 'expense')
            ->whereNull('budget_tracking_allocation_id')
            ->sum('amount');

        return [
            'total_income'       => round((float) $totalIncome, 2),
            'total_expense'      => round((float) $totalExpense, 2),
            'total_allocated'    => round((float) $totalAllocated, 2),
            'unallocated_expense'=> round((float) $unallocatedExpense, 2),
            'balance'            => round((float) $balance, 2),
            'remaining_budget'   => round(max(0, (float) $totalAllocated - $totalExpense), 2),
            'usage_pct'          => $totalAllocated > 0
                ? round(($totalExpense / $totalAllocated) * 100, 2)
                : 0,
            'is_over_budget'     => $totalExpense > $totalAllocated,
            'member_count'       => $budget->members()->count(),
            'allocations'        => $allocationSummary,
            'by_member'          => $memberSummary,
        ];
    }

    // ─── History ─────────────────────────────────────────────────────────────────

    /**
     * Get paginated change history for a budget tracking.
     */
    public function getHistory(BudgetTracking $budget, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 20;

        return BudgetTrackingHistory::with('user')
            ->where('budget_tracking_id', $budget->id)
            ->when(! empty($filters['action']),  fn($q) => $q->where('action', $filters['action']))
            ->when(! empty($filters['user_id']), fn($q) => $q->where('user_id', $filters['user_id']))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // ─── Consolidated Member Data ─────────────────────────────────────────────────

    /**
     * Return a full consolidated view of every member's financial data:
     * income, expenses, budgets, debts, investments, stocks, crypto, payments, purchases.
     * Every record includes a `user_name` key for attribution.
     */
    public function getConsolidatedData(BudgetTracking $budget): array
    {
        // Build a map of userId → name for fast lookup
        $members = $budget->members()->with('user')->get()->map(fn($m) => [
            'user_id'   => $m->user_id,
            'name'      => $m->user->name,
            'role'      => $m->role,
            'joined_at' => $m->joined_at,
        ]);

        $userIds  = $members->pluck('user_id')->toArray();
        $nameMap  = $members->pluck('name', 'user_id')->toArray();

        // Helper: append user_name to every model row
        $tag = function ($collection) use ($nameMap) {
            return $collection->map(function ($row) use ($nameMap) {
                $arr              = $row->toArray();
                $arr['user_name'] = $nameMap[$row->user_id] ?? 'Unknown';
                return $arr;
            })->values()->toArray();
        };

        // ── Fetch from every module scoped to the budget tracking ────────────────
        $btId        = $budget->id;
        $incomes     = Income::where('budget_tracking_id', $btId)->latest('received_at')->get();
        $expenses    = Expense::where('budget_tracking_id', $btId)->latest('spent_at')->get();
        $debts       = Debt::where('budget_tracking_id', $btId)->get();
        $investments = Investment::where('budget_tracking_id', $btId)->get();
        $stocks      = Stock::where('budget_tracking_id', $btId)->get();
        $crypto      = CryptoAsset::where('budget_tracking_id', $btId)->get();
        $payments    = Payment::with('debt')->where('budget_tracking_id', $btId)->latest()->get();
        $purchases   = Purchase::where('budget_tracking_id', $btId)->get();

        // ── Per-member summary for the overview panel ────────────────────────────
        $memberSummary = $members->map(function ($m) use (
            $incomes, $expenses, $debts, $investments, $stocks, $crypto
        ) {
            $uid = $m['user_id'];
            return [
                'user_id'          => $uid,
                'name'             => $m['name'],
                'role'             => $m['role'],
                'total_income'     => round((float) $incomes->where('user_id', $uid)->sum('amount'), 2),
                'total_expenses'   => round((float) $expenses->where('user_id', $uid)->sum('amount'), 2),
                'total_debt'       => round((float) $debts->where('user_id', $uid)->sum('remaining_balance'), 2),
                'total_invested'   => round((float) $investments->where('user_id', $uid)->sum('amount_invested'), 2),
                'total_invest_val' => round((float) $investments->where('user_id', $uid)->sum('current_value'), 2),
                'total_stocks_val' => round((float) $stocks->where('user_id', $uid)->sum(
                    fn($s) => (float) $s->shares * (float) $s->current_price
                ), 2),
                'total_crypto_val' => round((float) $crypto->where('user_id', $uid)->sum(
                    fn($a) => (float) $a->quantity * (float) $a->current_price
                ), 2),
            ];
        })->values()->toArray();

        return [
            'member_count'   => count($userIds),
            'member_summary' => $memberSummary,
            'income'         => $tag($incomes),
            'expenses'       => $tag($expenses),
            'debts'          => $tag($debts),
            'investments'    => $tag($investments),
            'stocks'         => $tag($stocks),
            'crypto'         => $tag($crypto),
            'payments'       => $tag($payments),
            'purchases'      => $tag($purchases),
        ];
    }

    // ─── Internal Helpers ─────────────────────────────────────────────────────────

    /**
     * Write an immutable history log entry.
     */
    private function log(
        BudgetTracking $budget,
        User           $user,
        string         $action,
        ?string        $subjectType,
        ?int           $subjectId,
        ?array         $oldValues,
        ?array         $newValues,
        string         $description
    ): void {
        BudgetTrackingHistory::create([
            'budget_tracking_id' => $budget->id,
            'user_id'            => $user->id,
            'action'             => $action,
            'subject_type'       => $subjectType,
            'subject_id'         => $subjectId,
            'old_values'         => $oldValues,
            'new_values'         => $newValues,
            'description'        => $description,
            'created_at'         => now(),
        ]);
    }

    /**
     * Assert the user is the owner of the budget tracking.
     *
     * @throws ValidationException
     */
    private function assertOwner(BudgetTracking $budget, User $user): void
    {
        if (! $budget->isOwner($user->id)) {
            throw ValidationException::withMessages([
                'budget_tracking' => ['Only the budget owner can perform this action.'],
            ]);
        }
    }
}
