<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;

class StockPolicy extends BasePolicy
{
    public function viewAny(User $user): bool  { return true; }
    public function view(User $user, Stock $stock): bool   { return $this->ownedByTracker($user, $stock); }
    public function create(User $user): bool   { return true; }
    public function update(User $user, Stock $stock): bool { return $this->ownedByTracker($user, $stock); }
    public function delete(User $user, Stock $stock): bool { return $this->ownedByTracker($user, $stock); }
}
