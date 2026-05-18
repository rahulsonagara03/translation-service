<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportTranslationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'string', 'max:12', 'regex:/^[a-z]{2,3}(?:[-_][A-Za-z0-9]{2,8})?$/'],
            'tags' => ['sometimes', 'string', 'max:255'],
        ];
    }
}