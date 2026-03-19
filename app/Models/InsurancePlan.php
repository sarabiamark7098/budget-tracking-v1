<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsurancePlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'provider_name',
        'plan_name',
        'coverage_type',
        'coverage_amount',
        'premium_amount',
        'payment_frequency',
        'next_payment_date',
        'policy_number',
        'description',
    ];

    protected $casts = [
        'coverage_amount' => 'decimal:2',
        'premium_amount' => 'decimal:2',
        'next_payment_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function insurancePayments(): HasMany
    {
        return $this->hasMany(InsurancePayment::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
