<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EngagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('engagement');

        return [
            'contrat_id'             => ['required', 'exists:contrats,id'],
            'numero'                 => [
                'required', 'string', 'max:100',
                Rule::unique('engagements', 'numero')->ignore($id)->whereNull('deleted_at'),
            ],
            'date_engagement'        => ['required', 'date'],
            'exercice'               => ['required', 'string', 'size:4'],
            'compte_budget_id'       => ['nullable', 'exists:comptes_budget,id'],
            'montant_engage'         => ['required', 'integer', 'min:1'],
            'statut'                 => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected', 'archived'])],
            'commentaire_validation' => ['nullable', 'string'],
        ];
    }
}
