<?php

namespace App\Core\Auth\Services;

use App\Models\User;

class ContextPermissionResolver
{
    public function permissionsFor(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $user->loadMissing('asignacionLaboralActiva');

        $permissions = data_get($user->asignacionLaboralActiva?->metadata, 'context_permissions', []);

        if (! is_array($permissions)) {
            return [];
        }

        return collect($permissions)
            ->filter(fn (mixed $permission): bool => is_string($permission) && $permission !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(?User $user, string $permission): bool
    {
        return in_array($permission, $this->permissionsFor($user), true);
    }
}
