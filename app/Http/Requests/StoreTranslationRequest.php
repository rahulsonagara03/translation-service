<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTranslationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'max:12', 'regex:/^[a-z]{2,3}(?:[-_][A-Za-z0-9]{2,8})?$/'],
            'group' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            'key' => [
                'required',
                'string',
                'max:191',
                'regex:/^[A-Za-z0-9_.:-]+$/',
                Rule::unique('translations', 'key')->where(
                    fn (Builder $query): Builder => $query
                        ->where('locale', $this->string('locale')->toString())
                        ->where('group', $this->string('group')->toString())
                ),
            ],
            'value' => ['required', 'string'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['required', 'string', 'distinct:ignore_case', 'max:64', 'regex:/^[A-Za-z0-9_.:-]+$/'],
        ];
    }
}