<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'budget_tracking_id',
        'category_id',
        'title',
        'amount',
        'source',
        'description',
        'received_at',
        'is_recurring',
        'recurrence_interval',
        'recurrence_end_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_at' => 'date',
        'is_recurring' => 'boolean',
        'recurrence_end_date' => 'date',
    ];

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
}
