<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->role === UserRole::Customer) {
            $user->customer()->create();
        }
    }
}
