<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_key' => ['required', 'string', 'max:120'],
            'event_key' => ['required', 'string', 'max:160'],
            'target_url' => ['required', 'url', 'max:2048'],
            'signing_secret' => ['nullable', 'string', 'min:12', 'max:255'],
            'custom_headers' => ['nullable', 'array'],
            'custom_headers.*' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
