<?php

namespace App\Services\Auth;

use App\DTO\Auth\UpdateUserDTO;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class updateUserService
{
    public function update(User $user, UpdateUserDTO $data): User
    {
        $attributes = $data->toArray();
        if ($data->imagen instanceof UploadedFile) {
            $attributes['imagen'] = $data->imagen->store('profiles', 'public');
        }

        $user->update($attributes);

        return $user->refresh();
    }
}
