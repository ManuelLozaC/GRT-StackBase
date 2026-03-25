<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SwitchActiveOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organizacion_id' => ['nullable', 'integer', 'exists:organizaciones,id', 'required_without:empresa_id'],
            'empresa_id' => ['nullable', 'integer', 'exists:organizaciones,id', 'required_without:organizacion_id'],
        ];
    }
}
