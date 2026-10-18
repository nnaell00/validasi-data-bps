<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ValidationBatch;

class ValidationBatchPolicy
{
    public function view(User $user, ValidationBatch $validationBatch): bool
    {
        return $user->isAdmin()
            || ($user->isPetugas() && $validationBatch->user_id === $user->id);
    }

    public function update(User $user, ValidationBatch $validationBatch): bool
    {
        return $this->view($user, $validationBatch);
    }
}
