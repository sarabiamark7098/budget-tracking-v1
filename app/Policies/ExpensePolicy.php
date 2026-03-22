<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy extends BasePolicy
{
    public function viewAny(User $user): bool   { return true; }
    public function view(User $user, Expense $expense): bool   { return $this->ownedByTracker($user, $expense); }
    public function create(User $user): bool    { return true; }
    public function update(User $user, Expense $expense): bool { return $this->ownedByTracker($user, $expense); }
    public function delete(User $user, Expense $expense): bool { return $this->ownedByTracker($user, $expense); }
}
