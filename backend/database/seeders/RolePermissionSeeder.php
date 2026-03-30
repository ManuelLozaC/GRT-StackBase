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
            ->push('modules.manage', 'settings.manage', 'integrations.manage', 'users.manage_roles', 'users.impersonate', 'roles.manage', 'tenancy.manage', 'security.manage')
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
            'mloza@grt.com.bo',
        ];

        User::query()
            ->whereIn('email', $adminCandidates)
            ->get()
            ->each(function (User $user) use ($adminRole): void {
                if (! $user->hasRole($adminRole->name)) {
                    $user->assignRole($adminRole);
                }
            });
    }
}
