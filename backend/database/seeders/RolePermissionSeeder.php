<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(config('modules.installed', []))
            ->flatMap(fn (array $module): array => $module['permissions'] ?? [])
            ->push('modules.manage', 'settings.manage', 'users.manage_roles', 'users.impersonate', 'tenancy.manage')
            ->unique()
            ->values()
            ->all();

        foreach ($permissions as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $adminRole = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $adminRole->syncPermissions($permissions);

        $adminCandidates = [
            'admin@stackbase.local',
            'cliente.admin@stackbase.local',
        ];

        User::query()
            ->whereIn('email', $adminCandidates)
            ->get()
            ->each(fn (User $user) => $user->syncRoles([$adminRole->name]));
    }
}
