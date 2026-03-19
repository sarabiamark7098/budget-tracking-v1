<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Investment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'type',
        'amount_invested',
        'current_value',
        'purchase_date',
        'description',
    ];

    protected $casts = [
        'amount_invested' => 'decimal:2',
        'current_value' => 'decimal:2',
        'purchase_date' => 'date',
    ];

    protected $appends = ['roi', 'roi_amount'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getROIAttribute(): float
    {
        $invested = (float) $this->amount_invested;
        if ($invested == 0) {
            return 0;
        }
        return round((((float) $this->current_value - $invested) / $invested) * 100, 2);
    }

    public function getROIAmountAttribute(): float
    {
        return (float) $this->current_value - (float) $this->amount_invested;
    }
}
