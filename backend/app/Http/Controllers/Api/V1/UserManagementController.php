<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Auth\Models\PersonalAccessToken;
use App\Core\Auth\Services\AccessTokenService;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Security\SecurityLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserRolesRequest;
use App\Models\Organizacion;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AccessTokenService $tokens,
        protected AuditLogger $auditLogger,
        protected SecurityLogger $securityLogger,
        protected MetricsRecorder $metrics,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $organizationId = $actor->organizacion_activa_id;

        $users = User::query()
            ->whereHas('organizaciones', fn ($query) => $query->whereKey($organizationId))
            ->with(['persona:id,nombres,apellido_paterno,apellido_materno,correo', 'organizacionActiva:id,nombre,slug', 'organizaciones:id,nombre,slug', 'roles:id,name'])
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            data: $users->map(fn (User $user): array => $this->transformUser($user))->all(),
            message: 'Usuarios listados',
            meta: [
                'available_roles' => Role::query()->orderBy('name')->pluck('name')->values()->all(),
                'available_personas' => $this->availablePersonas($organizationId),
                'organization_id' => $organizationId,
            ],
        );
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $organizationId = $actor->organizacion_activa_id;

        $payload = Validator::make($request->all(), [
            'persona_id' => ['nullable', 'integer', Rule::exists('personas', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'alias' => ['nullable', 'string', 'max:60', Rule::unique('users', 'alias')],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'telefono' => ['nullable', 'string', 'max:30'],
            'activo' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'primer_acceso_pendiente' => ['nullable', 'boolean'],
        ])->validate();

        $persona = $this->resolvePersonaForOrganization($payload['persona_id'] ?? null, $organizationId);

        if (($payload['persona_id'] ?? null) !== null && $persona === null) {
            return $this->errorResponse(
                message: 'La persona seleccionada no pertenece a la organizacion activa.',
                status: 422,
            );
        }

        $user = User::query()->create([
            'persona_id' => $persona?->id,
            'name' => $payload['name'],
            'alias' => $payload['alias'] ?? null,
            'email' => $payload['email'],
            'telefono' => $payload['telefono'] ?? null,
            'password' => $payload['password'],
            'activo' => $payload['activo'] ?? true,
            'primer_acceso_pendiente' => $payload['primer_acceso_pendiente'] ?? true,
            'expira_password_en' => Carbon::now()->addMonths(6),
            'organizacion_activa_id' => $organizationId,
        ]);

        $user->organizaciones()->syncWithoutDetaching([$organizationId]);
        $user->syncRoles($payload['roles'] ?? []);

        $freshUser = $user->fresh(['persona:id,nombres,apellido_paterno,apellido_materno,correo', 'organizacionActiva:id,nombre,slug', 'organizaciones:id,nombre,slug', 'roles:id,name']);

        $this->auditLogger->record(
            eventKey: 'user.created',
            actor: $actor,
            entityType: 'user',
            entityKey: (string) $user->id,
            summary: 'Se creo un usuario.',
            sourceModule: 'core-platform',
            organizationId: $organizationId,
        );

        return $this->successResponse(
            data: $this->transformUser($freshUser),
            message: 'Usuario creado correctamente',
        );
    }

    public function update(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $organizationId = $actor->organizacion_activa_id;

        if (! $this->userBelongsToOrganization($user, $organizationId)) {
            return $this->errorResponse(
                message: 'El usuario no pertenece a la organizacion activa.',
                status: 404,
            );
        }

        $payload = Validator::make($request->all(), [
            'persona_id' => ['nullable', 'integer', Rule::exists('personas', 'id')],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'alias' => ['nullable', 'string', 'max:60', Rule::unique('users', 'alias')->ignore($user->id)],
            'email' => ['sometimes', 'required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'telefono' => ['nullable', 'string', 'max:30'],
            'activo' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'primer_acceso_pendiente' => ['nullable', 'boolean'],
        ])->validate();

        $personaSpecified = array_key_exists('persona_id', $payload);
        $persona = $personaSpecified
            ? $this->resolvePersonaForOrganization($payload['persona_id'] ?? null, $organizationId)
            : null;

        if ($personaSpecified && ($payload['persona_id'] ?? null) !== null && $persona === null) {
            return $this->errorResponse(
                message: 'La persona seleccionada no pertenece a la organizacion activa.',
                status: 422,
            );
        }

        $user->fill([
            'persona_id' => $personaSpecified ? $persona?->id : $user->persona_id,
            'name' => $payload['name'] ?? $user->name,
            'alias' => array_key_exists('alias', $payload) ? $payload['alias'] : $user->alias,
            'email' => $payload['email'] ?? $user->email,
            'telefono' => array_key_exists('telefono', $payload) ? $payload['telefono'] : $user->telefono,
            'activo' => $payload['activo'] ?? $user->activo,
            'primer_acceso_pendiente' => $payload['primer_acceso_pendiente'] ?? $user->primer_acceso_pendiente,
        ])->save();

        if (array_key_exists('roles', $payload)) {
            $user->syncRoles($payload['roles'] ?? []);
        }

        return $this->successResponse(
            data: $this->transformUser($user->fresh(['persona:id,nombres,apellido_paterno,apellido_materno,correo', 'organizacionActiva:id,nombre,slug', 'organizaciones:id,nombre,slug', 'roles:id,name'])),
            message: 'Usuario actualizado correctamente',
        );
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $organizationId = $actor->organizacion_activa_id;

        if (! $this->userBelongsToOrganization($user, $organizationId)) {
            return $this->errorResponse(
                message: 'El usuario no pertenece a la organizacion activa.',
                status: 404,
            );
        }

        $payload = Validator::make($request->all(), [
            'activo' => ['required', 'boolean'],
        ])->validate();

        $user->forceFill([
            'activo' => $payload['activo'],
        ])->save();

        return $this->successResponse(
            data: $this->transformUser($user->fresh(['persona:id,nombres,apellido_paterno,apellido_materno,correo', 'organizacionActiva:id,nombre,slug', 'organizaciones:id,nombre,slug', 'roles:id,name'])),
            message: 'Estado de usuario actualizado',
        );
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $organizationId = $actor->organizacion_activa_id;

        if (! $this->userBelongsToOrganization($user, $organizationId)) {
            return $this->errorResponse(
                message: 'El usuario no pertenece a la organizacion activa.',
                status: 404,
            );
        }

        $payload = Validator::make($request->all(), [
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ])->validate();

        $user->forceFill([
            'password' => $payload['password'],
            'primer_acceso_pendiente' => true,
            'expira_password_en' => Carbon::now()->addMonths(6),
        ])->save();

        $user->accessTokens()->delete();

        return $this->successResponse(
            data: $this->transformUser($user->fresh(['persona:id,nombres,apellido_paterno,apellido_materno,correo', 'organizacionActiva:id,nombre,slug', 'organizaciones:id,nombre,slug', 'roles:id,name'])),
            message: 'Contrasena restablecida correctamente',
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

        $this->securityLogger->log(
            eventKey: 'security.roles_updated',
            actor: $request->user(),
            severity: 'warning',
            summary: 'Se actualizaron roles de un usuario.',
            context: [
                'target_user_id' => $user->id,
                'roles' => $user->getRoleNames()->values()->all(),
            ],
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'user.roles_updated',
            eventCategory: 'rbac',
            actor: $request->user(),
            context: [
                'target_user_id' => $user->id,
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

        $this->securityLogger->log(
            eventKey: 'security.impersonation_started',
            actor: $actor,
            severity: 'warning',
            summary: 'Se inicio una impersonacion.',
            context: [
                'target_user_id' => $targetUser->id,
                'target_email' => $targetUser->email,
            ],
            organizationId: $actor->organizacion_activa_id,
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'user.impersonation_started',
            eventCategory: 'support',
            actor: $actor,
            context: [
                'target_user_id' => $targetUser->id,
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

        $this->securityLogger->log(
            eventKey: 'security.impersonation_finished',
            actor: $impersonator,
            severity: 'warning',
            summary: 'Se cerro una impersonacion.',
            context: [
                'target_user_id' => $currentUser->id,
                'target_email' => $currentUser->email,
            ],
            organizationId: $impersonator->organizacion_activa_id,
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'user.impersonation_finished',
            eventCategory: 'support',
            actor: $impersonator,
            context: [
                'target_user_id' => $currentUser->id,
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
            'alias' => $user->alias,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'activo' => (bool) $user->activo,
            'primer_acceso_pendiente' => (bool) $user->primer_acceso_pendiente,
            'expira_password_en' => $user->expira_password_en?->toIso8601String(),
            'persona' => $user->persona ? [
                'id' => $user->persona->id,
                'nombre' => trim(implode(' ', array_filter([
                    $user->persona->nombres,
                    $user->persona->apellido_paterno,
                    $user->persona->apellido_materno,
                ]))),
                'correo' => $user->persona->correo,
            ] : null,
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

        return $user->fresh(['persona:id,nombres,apellido_paterno,apellido_materno,correo', 'organizacionActiva:id,nombre,slug', 'organizaciones:id,nombre,slug', 'roles:id,name']);
    }

    protected function availablePersonas(?int $organizationId): array
    {
        if ($organizationId === null) {
            return [];
        }

        return Persona::query()
            ->where('organizacion_id', $organizationId)
            ->orderBy('nombres')
            ->get(['id', 'nombres', 'apellido_paterno', 'apellido_materno', 'correo'])
            ->map(function (Persona $persona): array {
                return [
                    'id' => $persona->id,
                    'label' => trim(implode(' ', array_filter([
                        $persona->nombres,
                        $persona->apellido_paterno,
                        $persona->apellido_materno,
                    ]))),
                    'correo' => $persona->correo,
                ];
            })
            ->all();
    }

    protected function resolvePersonaForOrganization(?int $personaId, ?int $organizationId): ?Persona
    {
        if ($personaId === null || $organizationId === null) {
            return null;
        }

        return Persona::query()
            ->where('organizacion_id', $organizationId)
            ->whereKey($personaId)
            ->first();
    }

    protected function userBelongsToOrganization(User $user, ?int $organizationId): bool
    {
        if ($organizationId === null) {
            return false;
        }

        return $user->organizaciones()
            ->whereKey($organizationId)
            ->exists();
    }
}
