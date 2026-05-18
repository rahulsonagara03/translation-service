<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchTranslationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'string', 'max:12', 'regex:/^[a-z]{2,3}(?:[-_][A-Za-z0-9]{2,8})?$/'],
            'group' => ['sometimes', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            'key' => ['sometimes', 'string', 'max:191'],
            'content' => ['sometimes', 'string', 'max:255'],
            'q' => ['sometimes', 'string', 'max:255'],
            'tags' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'tag_mode' => ['sometimes', Rule::in(['all', 'any'])],
        ];
    }
}