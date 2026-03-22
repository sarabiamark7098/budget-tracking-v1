<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy extends BasePolicy
{
    public function viewAny(User $user): bool   { return true; }
    public function view(User $user, Purchase $purchase): bool   { return $this->ownedByTracker($user, $purchase); }
    public function create(User $user): bool    { return true; }
    public function update(User $user, Purchase $purchase): bool { return $this->ownedByTracker($user, $purchase); }
    public function delete(User $user, Purchase $purchase): bool { return $this->ownedByTracker($user, $purchase); }
}
