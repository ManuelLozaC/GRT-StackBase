<?php

namespace App\Http\Requests\Api\V1\Demo;

use Illuminate\Foundation\Http\FormRequest;

class CreateTemporaryFileLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ttl_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ];
    }
}
