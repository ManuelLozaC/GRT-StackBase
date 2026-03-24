<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IniciarSesionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identificador' => ['required', 'string'],
            'password' => ['required', 'string'],
            'oficina_id' => ['nullable', 'integer'],
            'device_name' => ['nullable', 'string', 'max:150'],
        ];
    }
}
