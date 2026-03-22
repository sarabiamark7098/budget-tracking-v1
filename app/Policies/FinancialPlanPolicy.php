<?php

namespace App\Policies;

use App\Models\FinancialPlan;
use App\Models\User;

class FinancialPlanPolicy extends BasePolicy
{
    public function viewAny(User $user): bool   { return true; }
    public function view(User $user, FinancialPlan $plan): bool   { return $this->ownedByTracker($user, $plan); }
    public function create(User $user): bool    { return true; }
    public function update(User $user, FinancialPlan $plan): bool { return $this->ownedByTracker($user, $plan); }
    public function delete(User $user, FinancialPlan $plan): bool { return $this->ownedByTracker($user, $plan); }
}
