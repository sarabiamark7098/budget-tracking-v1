<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool    { return true; }
    public function view(User $user, Payment $payment): bool   { return $this->ownedByTracker($user, $payment); }
    public function create(User $user): bool     { return true; }
    public function delete(User $user, Payment $payment): bool { return $this->ownedByTracker($user, $payment); }
}
