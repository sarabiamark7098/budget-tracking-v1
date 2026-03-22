<?php

namespace App\Policies;

use App\Models\Investment;
use App\Models\User;

class InvestmentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool   { return true; }
    public function view(User $user, Investment $investment): bool   { return $this->ownedByTracker($user, $investment); }
    public function create(User $user): bool    { return true; }
    public function update(User $user, Investment $investment): bool { return $this->ownedByTracker($user, $investment); }
    public function delete(User $user, Investment $investment): bool { return $this->ownedByTracker($user, $investment); }
}
