<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SwitchActiveWorkAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asignacion_laboral_id' => ['required', 'integer', 'exists:asignaciones_laborales,id'],
        ];
    }
}
