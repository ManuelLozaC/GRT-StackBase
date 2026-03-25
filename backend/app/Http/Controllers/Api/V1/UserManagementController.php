<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Models\PersonalAccessToken;
use App\Core\Auth\Services\AccessTokenService;
use App\Core\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserRolesRequest;
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AccessTokenService $tokens,
        protected AuditLogger $auditLogger,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $organizationId = $actor->organizacion_activa_id;

        $users = User::query()
            ->whereHas('organizaciones', fn ($query) => $query->whereKey($organizationId))
            ->with(['organizacionActiva:id,nombre,slug', 'organizaciones:id,nombre,slug', 'roles:id,name'])
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            data: $users->map(fn (User $user): array => $this->transformUser($user))->all(),
            message: 'Usuarios listados',
            meta: [
                'available_roles' => Role::query()->orderBy('name')->pluck('name')->values()->all(),
                'organization_id' => $organizationId,
            ],
        );
    }

    public function updateRoles(UpdateUserRolesRequest $request, User $user): JsonResponse
    {
        $user->syncRoles($request->input('roles', []));

        $this->auditLogger->record(
            eventKey: 'user.roles_updated',
            actor: $request->user(),
            entityType: 'user',
            entityKey: (string) $user->id,
            summary: 'Se actualizaron los roles de un usuario.',
            sourceModule: 'core-platform',
            context: [
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        );

        return $this->successResponse(
            data: $this->transformUser($user->fresh(['organizacionActiva:id,nombre,slug', 'organizaciones:id,nombre,slug', 'roles:id,name'])),
            message: 'Roles actualizados',
        );
    }

    public function impersonate(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        if ($actor->is($user)) {
            return $this->errorResponse(
                message: 'No puedes impersonarte a ti mismo.',
                status: 422,
            );
        }

        $sharesOrganization = $user->organizaciones()
            ->whereKey($actor->organizacion_activa_id)
            ->exists();

        if (! $sharesOrganization) {
            return $this->errorResponse(
                message: 'Solo puedes impersonar usuarios de la organizacion activa.',
                status: 403,
            );
        }

        $targetUser = $this->ensureActiveOrganization($user->fresh());
        $currentToken = $request->attributes->get('current_access_token');
        $this->tokens->revokeCurrent($currentToken instanceof PersonalAccessToken ? $currentToken : null);

        $token = $this->tokens->createForUser(
            user: $targetUser,
            name: 'impersonated-session',
            ttlMinutes: 60 * 4,
            metadata: [
                'impersonated_by' => $actor->id,
                'impersonated_by_name' => $actor->name,
                'impersonated_by_email' => $actor->email,
            ],
        );

        $this->auditLogger->record(
            eventKey: 'user.impersonation_started',
            actor: $actor,
            entityType: 'user',
            entityKey: (string) $targetUser->id,
            summary: 'Se inicio una impersonacion.',
            sourceModule: 'core-platform',
            context: [
                'target_email' => $targetUser->email,
            ],
            organizationId: $actor->organizacion_activa_id,
        );

        return $this->successResponse(
            data: [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->transformUser(
                    $targetUser,
                    [
                        'active' => true,
                        'impersonated_by' => [
                            'id' => $actor->id,
                            'name' => $actor->name,
                            'email' => $actor->email,
                        ],
                    ],
                ),
            ],
            message: 'Impersonacion iniciada',
        );
    }

    public function leaveImpersonation(Request $request): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        /** @var PersonalAccessToken|null $token */
        $token = $request->attributes->get('current_access_token');
        $impersonatorId = (int) ($token?->metadata['impersonated_by'] ?? 0);

        if ($impersonatorId <= 0) {
            return $this->errorResponse(
                message: 'La sesion actual no esta impersonando a otro usuario.',
                status: 422,
            );
        }

        $impersonator = User::query()->find($impersonatorId);

        if ($impersonator === null) {
            return $this->errorResponse(
                message: 'No se pudo restaurar el usuario original.',
                status: 404,
            );
        }

        $this->tokens->revokeCurrent($token);
        $restoredUser = $this->ensureActiveOrganization($impersonator);
        $restoredToken = $this->tokens->createForUser(
            user: $restoredUser,
            name: 'restored-session',
            ttlMinutes: 60 * 8,
        );

        $this->auditLogger->record(
            eventKey: 'user.impersonation_finished',
            actor: $impersonator,
            entityType: 'user',
            entityKey: (string) $currentUser->id,
            summary: 'Se cerro una impersonacion.',
            sourceModule: 'core-platform',
            context: [
                'target_email' => $currentUser->email,
            ],
            organizationId: $impersonator->organizacion_activa_id,
        );

        return $this->successResponse(
            data: [
                'token' => $restoredToken,
                'token_type' => 'Bearer',
                'user' => $this->transformUser($restoredUser),
            ],
            message: 'Impersonacion finalizada',
        );
    }

    protected function transformUser(User $user, array $impersonation = []): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'organizacion_activa' => $this->transformOrganization($user->organizacionActiva),
            'organizaciones' => $user->organizaciones
                ->map(fn (Organizacion $organizacion): array => $this->transformOrganization($organizacion))
                ->values()
                ->all(),
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            'impersonation' => array_merge([
                'active' => false,
                'impersonated_by' => null,
            ], $impersonation),
        ];
    }

    protected function transformOrganization(?Organizacion $organizacion): ?array
    {
        if ($organizacion === null) {
            return null;
        }

        return [
            'id' => $organizacion->id,
            'nombre' => $organizacion->nombre,
            'slug' => $organizacion->slug,
        ];
    }

    protected function ensureActiveOrganization(?User $user): ?User
    {
        if ($user === null) {
            return null;
        }

        $user->loadMissing('organizaciones:id');

        $organizationIds = $user->organizaciones->pluck('id')->values();

        if ($organizationIds->isEmpty()) {
            return $user;
        }

        if (! $organizationIds->contains($user->organizacion_activa_id)) {
            $user->forceFill([
                'organizacion_activa_id' => $organizationIds->first(),
            ])->save();
        }

        return $user->fresh(['organizacionActiva:id,nombre,slug', 'organizaciones:id,nombre,slug', 'roles:id,name']);
    }
}
