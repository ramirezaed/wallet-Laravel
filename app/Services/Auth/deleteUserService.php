<?php

namespace App\Services\Auth;

use App\Models\User;

class deleteUserService
{
    public function delete(User $user): User
    {
        $user->update(['eliminado' => true]);
        $user->delete();

        return $user;
    }
}
