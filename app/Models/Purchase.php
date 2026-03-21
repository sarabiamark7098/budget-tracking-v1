<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'category_id',
        'item_name',
        'description',
        'total_cost',
        'is_installment',
        'installment_count',
        'installment_amount',
        'installments_paid',
        'purchase_date',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'is_installment' => 'boolean',
        'purchase_date' => 'date',
    ];

    protected $appends = ['remaining_installments'];

    public function budgetTracking(): BelongsTo
    {
        return $this->belongsTo(BudgetTracking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function getRemainingInstallmentsAttribute(): int
    {
        if (!$this->is_installment || !$this->installment_count) {
            return 0;
        }
        return max(0, $this->installment_count - $this->installments_paid);
    }
}
