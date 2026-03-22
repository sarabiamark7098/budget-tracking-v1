<?php

namespace App\Policies;

use App\Models\Debt;
use App\Models\User;

class DebtPolicy extends BasePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Debt $debt): bool   { return $this->ownedByTracker($user, $debt); }
    public function create(User $user): bool  { return true; }
    public function update(User $user, Debt $debt): bool { return $this->ownedByTracker($user, $debt); }
    public function delete(User $user, Debt $debt): bool { return $this->ownedByTracker($user, $debt); }
    /** Used for the /balance and /pay custom endpoints. */
    public function pay(User $user, Debt $debt): bool    { return $this->ownedByTracker($user, $debt); }
}
