<?php

namespace App\Services\Admin;

use App\DTO\Admin\UpdateAdminUserDTO;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class UpdateAdminUserService
{
    public function update(User $user, UpdateAdminUserDTO $data): User
    {
        $attributes = $data->toArray();

        if ($data->imagen instanceof UploadedFile) {
            $attributes['imagen'] = $data->imagen->store('profiles', 'public');
        }

        $user->update($attributes);

        return $user->refresh();
    }
}
