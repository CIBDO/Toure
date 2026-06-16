<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompteBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('compte_budget');

        return [
            'code'              => ['required', 'string', 'max:50', Rule::unique('comptes_budget', 'code')->ignore($id)->whereNull('deleted_at')],
            'libelle'           => ['required', 'string', 'max:255'],
            'exercice'          => ['required', 'string', 'size:4'],
            'montant_alloue'    => ['required', 'numeric', 'min:0'],
            'montant_engage'    => ['nullable', 'numeric', 'min:0'],
            'montant_disponible'=> ['nullable', 'numeric', 'min:0'],
            'description'       => ['nullable', 'string'],
            'actif'             => ['boolean'],
        ];
    }
}
