<?php

namespace App\Policies;

use App\Models\FinancialGoal;
use App\Models\User;

class FinancialGoalPolicy extends BasePolicy
{
    public function viewAny(User $user): bool   { return true; }
    public function view(User $user, FinancialGoal $goal): bool   { return $this->ownedByTracker($user, $goal); }
    public function create(User $user): bool    { return true; }
    public function update(User $user, FinancialGoal $goal): bool { return $this->ownedByTracker($user, $goal); }
    public function delete(User $user, FinancialGoal $goal): bool { return $this->ownedByTracker($user, $goal); }
}
