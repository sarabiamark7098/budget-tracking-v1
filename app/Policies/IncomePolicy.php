<?php

namespace App\Policies;

use App\Models\Income;
use App\Models\User;

class IncomePolicy extends BasePolicy
{
    public function viewAny(User $user): bool  { return true; }
    public function view(User $user, Income $income): bool   { return $this->ownedByTracker($user, $income); }
    public function create(User $user): bool   { return true; }
    public function update(User $user, Income $income): bool { return $this->ownedByTracker($user, $income); }
    public function delete(User $user, Income $income): bool { return $this->ownedByTracker($user, $income); }
}
