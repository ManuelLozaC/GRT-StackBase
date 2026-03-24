<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IniciarSesionRequest;
use App\Models\User;
use App\Support\RespuestaApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    public function login(IniciarSesionRequest $request)
    {
        $credenciales = $request->validated();

        $usuario = User::query()
            ->with(['persona', 'organizacion', 'asignacionesLaborales'])
            ->where(static function ($query) use ($credenciales): void {
                $query->where('email', $credenciales['identificador'])
                    ->orWhere('alias', $credenciales['identificador']);
            })
            ->first();

        if (! $usuario || ! Hash::check($credenciales['password'], $usuario->password) || ! $usuario->activo) {
            return RespuestaApi::error('Las credenciales no son válidas.', [
                'credenciales' => ['Correo/usuario o contraseña incorrectos.'],
            ], status: 422);
        }

        $oficinaId = $credenciales['oficina_id'] ?? $usuario->asignacionesLaborales()
            ->where('activa', true)
            ->orderByDesc('es_principal')
            ->value('oficina_id');

        app(PermissionRegistrar::class)->setPermissionsTeamId($oficinaId);

        $usuario->forceFill([
            'ultimo_acceso_at' => now(),
        ])->save();

        $token = $usuario->createToken($credenciales['device_name'] ?? 'spa')->plainTextToken;

        return RespuestaApi::ok([
            'token' => $token,
            'usuario' => $usuario->load(['persona', 'organizacion', 'asignacionesLaborales']),
            'oficina_id_activa' => $oficinaId,
        ], 'Sesión iniciada correctamente.');
    }

    public function me(Request $request)
    {
        $usuario = $request->user()?->load(['persona', 'organizacion', 'asignacionesLaborales']);

        return RespuestaApi::ok([
            'usuario' => $usuario,
            'permisos' => $usuario?->getAllPermissions()->pluck('name')->values(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return RespuestaApi::ok(mensaje: 'Sesión cerrada correctamente.');
    }

    public function enviarEnlaceRecuperacion(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $estado = Password::sendResetLink([
            'email' => $request->string('email')->toString(),
        ]);

        if ($estado !== Password::RESET_LINK_SENT) {
            return RespuestaApi::error('No se pudo enviar el enlace de recuperación.', [
                'email' => [$estado],
            ]);
        }

        return RespuestaApi::ok(mensaje: 'Se envió el enlace de recuperación.');
    }
}
