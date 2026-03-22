<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy extends BasePolicy
{
    public function viewAny(User $user): bool { return true; }

    /** View/download: user must belong to the same BudgetTracking as the file. */
    public function view(User $user, File $file): bool
    {
        return $this->ownedByTracker($user, $file);
    }

    public function create(User $user): bool { return true; }

    /**
     * Delete: only the user who uploaded the file may delete it.
     * Being a member of the BudgetTracking is not sufficient for deletion —
     * this prevents members from deleting each other's attachments.
     */
    public function delete(User $user, File $file): bool
    {
        return $file->user_id === $user->id;
    }
}
