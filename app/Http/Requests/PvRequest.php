<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('pv');

        return [
            'reference'                   => ['required', 'string', 'max:100', Rule::unique('pvs', 'reference')->ignore($id)->whereNull('deleted_at')],
            'depouillement_id'            => ['nullable', 'exists:depouillements,id'],
            'avis_id'                     => ['required', 'exists:avis,id'],
            'fournisseur_attributaire_id' => ['nullable', 'exists:fournisseurs,id'],
            'date_pv'                     => ['required', 'date'],
            'type_pv'                     => ['required', Rule::in(['attribution', 'infructueux', 'annulation'])],
            'montant_retenu'              => ['nullable', 'numeric', 'min:0'],
            'nb_soumission'               => ['nullable', 'integer', 'min:0'],
            'contenu'                     => ['nullable', 'string'],
            'statut'                      => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected', 'archived'])],
            'observations'                => ['nullable', 'string'],
            'motif_rejet'                 => ['nullable', 'string', 'max:500'],
        ];
    }
}
