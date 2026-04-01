<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Security\SecurityLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuditLogger $auditLogger,
        protected SecurityLogger $securityLogger,
    ) {
    }

    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get();

        $permissions = Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        return $this->successResponse(
            data: $roles->map(fn (Role $role): array => $this->transformRole($role))->all(),
            message: 'Roles listados',
            meta: [
                'available_permissions' => $permissions,
            ],
        );
    }

    public function store(Request $request): JsonResponse
    {
        $payload = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120', Rule::unique('roles', 'name')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ])->validate();

        $role = Role::query()->create([
            'name' => $payload['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($payload['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role->load('permissions:id,name');

        $this->auditLogger->record(
            eventKey: 'role.created',
            actor: $request->user(),
            entityType: 'role',
            entityKey: (string) $role->id,
            summary: 'Se creo un rol.',
            sourceModule: 'core-platform',
            context: [
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ],
        );

        return $this->successResponse(
            data: $this->transformRole($role->fresh('permissions:id,name')),
            message: 'Rol creado correctamente',
        );
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $payload = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ])->validate();

        $previousName = $role->name;
        $previousPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

        $role->forceFill([
            'name' => $payload['name'],
        ])->save();

        $role->syncPermissions($payload['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role->load('permissions:id,name');

        $currentPermissions = $role->permissions->pluck('name')->sort()->values()->all();
        $addedPermissions = array_values(array_diff($currentPermissions, $previousPermissions));
        $removedPermissions = array_values(array_diff($previousPermissions, $currentPermissions));

        $this->auditLogger->record(
            eventKey: 'role.updated',
            actor: $request->user(),
            entityType: 'role',
            entityKey: (string) $role->id,
            summary: 'Se actualizo un rol.',
            sourceModule: 'core-platform',
            context: [
                'role' => $role->name,
                'previous_name' => $previousName,
                'permissions' => $currentPermissions,
                'previous_permissions' => $previousPermissions,
                'added_permissions' => $addedPermissions,
                'removed_permissions' => $removedPermissions,
            ],
        );

        $this->securityLogger->log(
            eventKey: 'security.role_permissions_updated',
            actor: $request->user(),
            severity: 'warning',
            summary: 'Se actualizaron permisos de un rol.',
            context: [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'added_permissions' => $addedPermissions,
                'removed_permissions' => $removedPermissions,
            ],
        );

        return $this->successResponse(
            data: $this->transformRole($role),
            message: 'Rol actualizado correctamente',
        );
    }

    protected function transformRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions
                ->pluck('name')
                ->sort()
                ->values()
                ->all(),
        ];
    }
}
