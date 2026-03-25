<?php

namespace App\Http\Requests\Api\V1\Demo;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemoFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
