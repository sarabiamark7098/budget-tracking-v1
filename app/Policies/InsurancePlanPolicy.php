<?php

namespace App\Policies;

use App\Models\InsurancePlan;
use App\Models\User;

class InsurancePlanPolicy extends BasePolicy
{
    public function viewAny(User $user): bool   { return true; }
    public function view(User $user, InsurancePlan $plan): bool   { return $this->ownedByTracker($user, $plan); }
    public function create(User $user): bool    { return true; }
    public function update(User $user, InsurancePlan $plan): bool { return $this->ownedByTracker($user, $plan); }
    public function delete(User $user, InsurancePlan $plan): bool { return $this->ownedByTracker($user, $plan); }
}
