<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Core\Auth\Models\PersonalAccessToken;
use App\Core\Auth\Services\AccessTokenService;
use App\Core\Http\Concerns\ApiResponse;
use App\Core\Metrics\MetricsRecorder;
use App\Core\Security\SecurityLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\SwitchActiveOrganizationRequest;
use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AccessTokenService $tokens,
        protected SecurityLogger $securityLogger,
        protected MetricsRecorder $metrics,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $login = trim($request->string('email')->toString());
        $password = $request->string('password')->toString();

        $user = User::query()
            ->where(function ($query) use ($login): void {
                $query->where('email', $login)
                    ->orWhere('alias', $login);
            })
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            $this->securityLogger->log(
                eventKey: 'auth.login_failed',
                severity: 'warning',
                summary: 'Intento de login fallido.',
                context: [
                    'login' => $login,
                ],
                organizationId: $user?->organizacion_activa_id,
            );

            return $this->errorResponse(
                message: 'Credenciales invalidas',
                status: 422,
            );
        }

        $token = $this->tokens->createForUser(
            user: $user,
            name: $request->string('device_name')->toString() ?: 'frontend',
            ttlMinutes: 60 * 8,
        );

        $this->securityLogger->log(
            eventKey: 'auth.login_succeeded',
            actor: $user,
            severity: 'info',
            summary: 'Sesion iniciada correctamente.',
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'auth.login_succeeded',
            eventCategory: 'auth',
            actor: $user,
        );

        return $this->successResponse(
            data: [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->transformUser($this->ensureActiveOrganization($user)),
            ],
            message: 'Sesion iniciada',
        );
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $organizationName = $request->string('organization_name')->toString()
            ?: $request->string('name')->toString().' Workspace';

        $organizacion = Organizacion::query()->create([
            'nombre' => $organizationName,
            'slug' => $this->generateUniqueOrganizationSlug($organizationName),
            'metadata' => [
                'source' => 'self-registration',
            ],
        ]);

        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'organizacion_activa_id' => $organizacion->id,
        ]);

        $user->organizaciones()->attach($organizacion->id);

        $token = $this->tokens->createForUser(
            user: $user,
            name: $request->string('device_name')->toString() ?: 'frontend-register',
            ttlMinutes: 60 * 8,
        );

        $this->securityLogger->log(
            eventKey: 'auth.registered',
            actor: $user,
            severity: 'info',
            summary: 'Se registro un nuevo usuario.',
            organizationId: $organizacion->id,
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'auth.registered',
            eventCategory: 'auth',
            actor: $user,
            organizationId: $organizacion->id,
        );

        return $this->successResponse(
            data: [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->transformUser($user->fresh()),
            ],
            message: 'Usuario registrado correctamente',
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('email', $request->string('email')->toString())
            ->first();

        $meta = [];

        if ($user !== null && app()->environment(['local', 'testing'])) {
            $meta['debug_reset_token_preview'] = Password::broker()->createToken($user);
        }

        $this->securityLogger->log(
            eventKey: 'auth.password_recovery_requested',
            actor: $user,
            severity: 'info',
            summary: 'Se solicito recuperacion de password.',
            context: [
                'email' => $request->string('email')->toString(),
            ],
            organizationId: $user?->organizacion_activa_id,
        );

        return $this->successResponse(
            data: null,
            message: 'Si el email existe, se genero un token de recuperacion',
            meta: $meta,
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->accessTokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->securityLogger->log(
                eventKey: 'auth.password_reset_failed',
                severity: 'warning',
                summary: 'Fallo un intento de reset de password.',
                context: [
                    'email' => $request->string('email')->toString(),
                ],
            );

            return $this->errorResponse(
                message: 'No se pudo restablecer la contrasena con ese token',
                status: 422,
            );
        }

        $resetUser = User::query()->where('email', $request->string('email')->toString())->first();

        $this->securityLogger->log(
            eventKey: 'auth.password_reset_succeeded',
            actor: $resetUser,
            severity: 'info',
            summary: 'Se restablecio una contrasena.',
            organizationId: $resetUser?->organizacion_activa_id,
        );

        return $this->successResponse(
            data: null,
            message: 'Contrasena restablecida correctamente',
        );
    }

    public function me(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken|null $token */
        $token = $request->attributes->get('current_access_token');

        return $this->successResponse(
            data: $this->transformUser(
                $this->ensureActiveOrganization($request->user()),
                $this->impersonationPayload($token),
            ),
            message: 'Usuario autenticado',
        );
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken|null $token */
        $token = $request->attributes->get('current_access_token');
        $this->tokens->revokeCurrent($token);

        $this->securityLogger->log(
            eventKey: 'auth.logout',
            actor: $request->user(),
            severity: 'info',
            summary: 'Sesion cerrada.',
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'auth.logout',
            eventCategory: 'auth',
            actor: $request->user(),
        );

        return $this->successResponse(
            data: null,
            message: 'Sesion cerrada',
        );
    }

    public function switchActiveOrganization(SwitchActiveOrganizationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $organizacionId = $request->integer('organizacion_id');

        $hasAccess = $user->organizaciones()
            ->whereKey($organizacionId)
            ->exists();

        if (! $hasAccess) {
            return $this->errorResponse(
                message: 'No tienes acceso a la organizacion seleccionada',
                status: 403,
            );
        }

        $user->forceFill([
            'organizacion_activa_id' => $organizacionId,
        ])->save();

        $user->unsetRelation('organizacionActiva');
        $user->unsetRelation('organizaciones');

        $this->securityLogger->log(
            eventKey: 'auth.organization_switched',
            actor: $user,
            severity: 'info',
            summary: 'Se cambio la organizacion activa.',
            context: [
                'organizacion_id' => $organizacionId,
            ],
            organizationId: $organizacionId,
        );
        $this->metrics->record(
            moduleKey: 'core-platform',
            eventKey: 'auth.organization_switched',
            eventCategory: 'tenancy',
            actor: $user,
            context: [
                'organizacion_id' => $organizacionId,
            ],
            organizationId: $organizacionId,
        );

        return $this->successResponse(
            data: $this->transformUser($user->fresh()),
            message: 'Organizacion activa actualizada',
        );
    }

    protected function transformUser(?User $user, array $impersonation = []): ?array
    {
        if ($user === null) {
            return null;
        }

        $user->loadMissing([
            'organizacionActiva:id,nombre,slug',
            'organizaciones:id,nombre,slug',
        ]);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'alias' => $user->alias,
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

        $organizacionIds = $user->organizaciones
            ->pluck('id')
            ->values();

        if ($organizacionIds->isEmpty()) {
            if ($user->organizacion_activa_id !== null) {
                $user->forceFill([
                    'organizacion_activa_id' => null,
                ])->save();
            }

            return $user->fresh();
        }

        if ($organizacionIds->contains($user->organizacion_activa_id)) {
            return $user;
        }

        $user->forceFill([
            'organizacion_activa_id' => $organizacionIds->first(),
        ])->save();

        return $user->fresh();
    }

    protected function generateUniqueOrganizationSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'workspace';
        $slug = $baseSlug;
        $suffix = 1;

        while (Organizacion::query()->where('slug', $slug)->exists()) {
            $suffix++;
            $slug = $baseSlug.'-'.$suffix;
        }

        return $slug;
    }

    protected function impersonationPayload(?PersonalAccessToken $token): array
    {
        $impersonatorId = $token?->metadata['impersonated_by'] ?? null;

        if (! $impersonatorId) {
            return [
                'active' => false,
                'impersonated_by' => null,
            ];
        }

        return [
            'active' => true,
            'impersonated_by' => [
                'id' => $impersonatorId,
                'name' => $token->metadata['impersonated_by_name'] ?? null,
                'email' => $token->metadata['impersonated_by_email'] ?? null,
            ],
        ];
    }
}
