<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;

class AlbumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('photos.view');
    }

    public function view(User $user, Album $album): bool
    {
        return $user->can('photos.view');
    }

    public function create(User $user): bool
    {
        return $user->can('photos.manage');
    }

    public function update(User $user, Album $album): bool
    {
        return $user->can('photos.manage');
    }

    public function delete(User $user, Album $album): bool
    {
        return $user->can('photos.manage');
    }
}
