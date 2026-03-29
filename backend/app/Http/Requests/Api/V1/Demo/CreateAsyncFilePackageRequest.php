<?php

namespace App\Http\Requests\Api\V1\Demo;

use Illuminate\Foundation\Http\FormRequest;

class CreateAsyncFilePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_uuids' => ['required', 'array', 'min:1', 'max:25'],
            'file_uuids.*' => ['required', 'string', 'max:36'],
        ];
    }
}
