<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('GED_EDIT') ?? false;
    }

    public function rules(): array
    {
        return [
            'category'     => ['sometimes', 'string', Rule::in(array_keys(config('ged.categories', [])))],
            'title'        => ['sometimes', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'date_document' => ['nullable', 'date'],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string', 'max:100'],
        ];
    }
}
