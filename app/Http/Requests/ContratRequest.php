<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContratRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('contrat');

        return [
            'reference'       => ['required', 'string', 'max:100', Rule::unique('contrats', 'reference')->ignore($id)->whereNull('deleted_at')],
            'objet'           => ['required', 'string', 'max:500'],
            'pv_id'           => ['nullable', 'exists:pvs,id'],
            'avis_id'         => ['nullable', 'exists:avis,id'],
            'fournisseur_id'  => ['required', 'exists:fournisseurs,id'],
            'compte_budget_id'=> ['nullable', 'exists:comptes_budget,id'],
            'agent_id'        => ['nullable', 'exists:users,id'],
            'montant_initial' => ['required', 'numeric', 'min:0'],
            'montant_actuel'  => ['nullable', 'numeric', 'min:0'],
            'devise'          => ['nullable', 'string', 'max:10'],
            'date_signature'  => ['nullable', 'date'],
            'date_debut'      => ['nullable', 'date'],
            'date_fin'        => ['nullable', 'date', 'after_or_equal:date_debut'],
            'duree_execution' => ['nullable', 'integer', 'min:1'],
            'mode_passation'  => ['nullable', 'string', 'max:50'],
            'exercice'        => ['nullable', 'string', 'size:4'],
            'statut'          => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected', 'archived'])],
            'observations'    => ['nullable', 'string'],
        ];
    }
}
