<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepouillementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('depouillement');

        return [
            'reference'          => ['required', 'string', 'max:100', Rule::unique('depouillements', 'reference')->ignore($id)->whereNull('deleted_at')],
            'avis_id'            => ['required', 'exists:avis,id'],
            'date_depouillement' => ['required', 'date'],
            'lieu'               => ['nullable', 'string', 'max:255'],
            'resultats'          => ['nullable', 'array'],
            'statut'             => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected'])],
            'observations'       => ['nullable', 'string'],
        ];
    }
}
