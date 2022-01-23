<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function show(User $user, Task $task): bool
    {
        return $user->id === $task->owner->id || $task->attached_users->contains($user);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->owner->id;
    }

    public function close(User $user, Task $task): bool
    {
        return $user->id === $task->owner->id || $task->attached_users->contains($user);
    }

    public function destroy(User $user, Task $task): bool
    {
        return $user->id === $task->owner->id;
    }
}
