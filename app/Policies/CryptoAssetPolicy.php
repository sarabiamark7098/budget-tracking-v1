<?php

namespace App\Policies;

use App\Models\CryptoAsset;
use App\Models\User;

class CryptoAssetPolicy extends BasePolicy
{
    public function viewAny(User $user): bool    { return true; }
    public function view(User $user, CryptoAsset $crypto): bool   { return $this->ownedByTracker($user, $crypto); }
    public function create(User $user): bool     { return true; }
    public function update(User $user, CryptoAsset $crypto): bool { return $this->ownedByTracker($user, $crypto); }
    public function delete(User $user, CryptoAsset $crypto): bool { return $this->ownedByTracker($user, $crypto); }
}
