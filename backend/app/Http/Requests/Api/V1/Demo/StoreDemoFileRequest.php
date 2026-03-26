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
            'attached_resource_key' => ['nullable', 'string', 'max:120'],
            'attached_record_id' => ['nullable', 'integer', 'min:1'],
            'attached_record_label' => ['nullable', 'string', 'max:180'],
        ];
    }
}
