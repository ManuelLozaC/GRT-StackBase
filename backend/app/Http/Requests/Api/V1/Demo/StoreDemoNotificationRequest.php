<?php

namespace App\Http\Requests\Api\V1\Demo;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemoNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:500'],
            'level' => ['nullable', 'in:info,success,warning,danger'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'channels' => ['nullable', 'array'],
            'channels.*' => ['string', 'in:internal,email,whatsapp,push'],
        ];
    }
}
