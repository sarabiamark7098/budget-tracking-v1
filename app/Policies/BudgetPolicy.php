<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy extends BasePolicy
{
    public function viewAny(User $user): bool  { return true; }
    public function view(User $user, Budget $budget): bool   { return $this->ownedByTracker($user, $budget); }
    public function create(User $user): bool   { return true; }
    public function update(User $user, Budget $budget): bool { return $this->ownedByTracker($user, $budget); }
    public function delete(User $user, Budget $budget): bool { return $this->ownedByTracker($user, $budget); }
}
