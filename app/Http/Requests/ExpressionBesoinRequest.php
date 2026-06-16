<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpressionBesoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('expressions_besoin');

        return [
            'code'                => ['required', 'string', 'max:30', Rule::unique('expressions_besoin', 'code')->ignore($id)->whereNull('deleted_at')],
            'libelle'             => ['required', 'string', 'max:500'],
            'description'         => ['nullable', 'string'],
            'unite_defaut'        => ['nullable', 'string', 'max:50'],
            'domaine_activite_id' => ['nullable', 'exists:domaines_activite,id'],
            'actif'               => ['boolean'],
        ];
    }
}
