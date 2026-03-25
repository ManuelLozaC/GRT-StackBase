<?php

namespace App\Http\Requests\Api\V1\Demo;

use Illuminate\Foundation\Http\FormRequest;

class DispatchDemoJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:500'],
            'mode' => ['nullable', 'in:queued,immediate'],
            'should_fail' => ['nullable', 'boolean'],
        ];
    }
}
