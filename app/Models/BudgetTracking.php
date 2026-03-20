<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
        'end_date',
        'join_code',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    protected $appends = ['total_allocated', 'total_income', 'total_expense', 'balance'];

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
