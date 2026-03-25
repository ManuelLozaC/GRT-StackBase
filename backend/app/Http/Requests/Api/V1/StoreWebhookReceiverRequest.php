<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookReceiverRequest extends FormRequest
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
            'source_name' => ['required', 'string', 'max:160'],
            'signing_secret' => ['required', 'string', 'min:12', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
