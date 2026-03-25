<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActualizarUsuarioRequest;
use App\Http\Requests\Api\GuardarUsuarioRequest;
use App\Http\Requests\Api\ResetearPasswordUsuarioRequest;
use App\Models\AsignacionLaboral;
use App\Models\Persona;
use App\Models\User;
use App\Support\RespuestaApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::query()
            ->with(['persona', 'organizacion', 'asignacionesLaborales'])
            ->orderBy('nombre_mostrar')
            ->paginate((int) $request->integer('per_page', 15));

        return RespuestaApi::ok($usuarios->items(), meta: [
            'pagina_actual' => $usuarios->currentPage(),
            'por_pagina' => $usuarios->perPage(),
            'total' => $usuarios->total(),
        ]);
    }

    public function store(GuardarUsuarioRequest $request)
    {
        $payload = $request->validated();

        $usuario = DB::transaction(function () use ($payload) {
            $persona = Persona::query()->create([
                ...$payload['persona'],
                'organizacion_id' => $payload['organizacion_id'] ?? auth()->user()?->organizacion_id,
                'activo' => true,
            ]);

            $usuario = User::query()->create([
                'organizacion_id' => $payload['organizacion_id'] ?? auth()->user()?->organizacion_id,
                'persona_id' => $persona->id,
                'alias' => $payload['alias'],
                'nombre_mostrar' => $payload['nombre_mostrar'],
                'email' => $payload['email'],
                'telefono' => $payload['telefono'] ?? null,
                'password' => $payload['password'],
                'es_superusuario' => $payload['es_superusuario'] ?? false,
                'activo' => $payload['activo'] ?? true,
                'debe_cambiar_password' => true,
            ]);

            foreach ($payload['asignaciones'] ?? [] as $asignacionPayload) {
                $this->guardarAsignacion($usuario, $persona, $asignacionPayload);
            }

            return $usuario->load(['persona', 'organizacion', 'asignacionesLaborales']);
        });

        return RespuestaApi::ok($usuario, 'Usuario creado correctamente.', status: 201);
    }

    public function show(User $user)
    {
        return RespuestaApi::ok($user->load(['persona', 'organizacion', 'asignacionesLaborales']));
    }

    public function update(ActualizarUsuarioRequest $request, User $user)
    {
        $payload = $request->validated();

        $usuario = DB::transaction(function () use ($payload, $user) {
            $user->update([
                'organizacion_id' => $payload['organizacion_id'] ?? $user->organizacion_id,
                'alias' => $payload['alias'],
                'nombre_mostrar' => $payload['nombre_mostrar'],
                'email' => $payload['email'],
                'telefono' => $payload['telefono'] ?? null,
                'es_superusuario' => $payload['es_superusuario'] ?? $user->es_superusuario,
                'activo' => $payload['activo'] ?? $user->activo,
            ]);

            if (! empty($payload['password'])) {
                $user->update(['password' => $payload['password']]);
            }

            $user->persona()->update([
                ...$payload['persona'],
                'organizacion_id' => $payload['organizacion_id'] ?? $user->organizacion_id,
            ]);

            $idsMantener = [];

            foreach ($payload['asignaciones'] ?? [] as $asignacionPayload) {
                $asignacion = $this->guardarAsignacion($user, $user->persona, $asignacionPayload);
                $idsMantener[] = $asignacion->id;
            }

            if ($idsMantener !== []) {
                $user->asignacionesLaborales()->whereNotIn('id', $idsMantener)->delete();
            }

            return $user->load(['persona', 'organizacion', 'asignacionesLaborales']);
        });

        return RespuestaApi::ok($usuario, 'Usuario actualizado correctamente.');
    }

    public function resetearPassword(ResetearPasswordUsuarioRequest $request, User $user)
    {
        $payload = $request->validated();

        $user->update([
            'password' => $payload['password'],
            'debe_cambiar_password' => $payload['debe_cambiar_password'] ?? true,
        ]);

        $user->tokens()->delete();

        return RespuestaApi::ok(mensaje: 'Contraseña reseteada correctamente.');
    }

    protected function guardarAsignacion(User $usuario, Persona $persona, array $payload): AsignacionLaboral
    {
        $asignacion = AsignacionLaboral::query()->updateOrCreate(
            ['id' => $payload['id'] ?? null],
            [
                'organizacion_id' => $usuario->organizacion_id,
                'persona_id' => $persona->id,
                'usuario_id' => $usuario->id,
                'oficina_id' => $payload['oficina_id'],
                'division_id' => $payload['division_id'] ?? null,
                'area_id' => $payload['area_id'] ?? null,
                'cargo_id' => $payload['cargo_id'] ?? null,
                'jefe_asignacion_laboral_id' => $payload['jefe_asignacion_laboral_id'] ?? null,
                'aprobador_asignacion_laboral_id' => $payload['aprobador_asignacion_laboral_id'] ?? null,
                'es_principal' => $payload['es_principal'] ?? false,
                'activa' => $payload['activa'] ?? true,
                'fecha_inicio' => $payload['fecha_inicio'] ?? now()->toDateString(),
                'fecha_fin' => $payload['fecha_fin'] ?? null,
                'observaciones' => $payload['observaciones'] ?? null,
            ],
        );

        if (! empty($payload['roles'])) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($asignacion->oficina_id);

            $roles = Role::query()
                ->whereIn('name', $payload['roles'])
                ->where(static function ($query) use ($asignacion): void {
                    $query->whereNull('oficina_id')
                        ->orWhere('oficina_id', $asignacion->oficina_id);
                })
                ->pluck('name')
                ->all();

            $usuario->syncRoles($roles);
        }

        return $asignacion;
    }
}
