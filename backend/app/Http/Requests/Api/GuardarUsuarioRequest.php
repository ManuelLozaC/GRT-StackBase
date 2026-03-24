<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organizacion_id' => ['nullable', 'integer', 'exists:organizaciones,id'],
            'alias' => ['required', 'string', 'max:100', 'unique:users,alias'],
            'nombre_mostrar' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'es_superusuario' => ['sometimes', 'boolean'],
            'activo' => ['sometimes', 'boolean'],
            'persona' => ['required', 'array'],
            'persona.nombres' => ['required', 'string', 'max:150'],
            'persona.apellido_paterno' => ['required', 'string', 'max:100'],
            'persona.apellido_materno' => ['nullable', 'string', 'max:100'],
            'persona.tipo_documento' => ['required', 'string', 'max:50'],
            'persona.numero_documento' => ['required', 'string', 'max:50'],
            'persona.genero' => ['required', 'string', 'max:20'],
            'persona.fecha_nacimiento' => ['nullable', 'date'],
            'persona.email' => ['nullable', 'email', 'max:150'],
            'persona.telefono' => ['nullable', 'string', 'max:30'],
            'persona.direccion' => ['nullable', 'string', 'max:255'],
            'persona.ciudad_id' => ['nullable', 'integer', 'exists:ciudades,id'],
            'asignaciones' => ['array'],
            'asignaciones.*.oficina_id' => ['required', 'integer', 'exists:oficinas,id'],
            'asignaciones.*.division_id' => ['nullable', 'integer', 'exists:divisiones,id'],
            'asignaciones.*.area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'asignaciones.*.cargo_id' => ['nullable', 'integer', 'exists:cargos,id'],
            'asignaciones.*.jefe_asignacion_laboral_id' => ['nullable', 'integer', 'exists:asignaciones_laborales,id'],
            'asignaciones.*.aprobador_asignacion_laboral_id' => ['nullable', 'integer', 'exists:asignaciones_laborales,id'],
            'asignaciones.*.es_principal' => ['sometimes', 'boolean'],
            'asignaciones.*.activa' => ['sometimes', 'boolean'],
            'asignaciones.*.fecha_inicio' => ['nullable', 'date'],
            'asignaciones.*.fecha_fin' => ['nullable', 'date'],
            'asignaciones.*.roles' => ['array'],
            'asignaciones.*.roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
