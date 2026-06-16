<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FournisseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('fournisseur');

        return [
            'code'                    => ['required', 'string', 'max:30', Rule::unique('fournisseurs', 'code')->ignore($id)->whereNull('deleted_at')],
            'civilite'                => ['nullable', 'string', 'max:20'],
            'qualite_fonction'        => ['nullable', 'string', 'max:100'],
            'raison_sociale'          => ['required', 'string', 'max:255'],
            'sigle'                   => ['nullable', 'string', 'max:50'],
            'nif'                     => ['nullable', 'string', 'max:50', Rule::unique('fournisseurs', 'nif')->ignore($id)->whereNull('deleted_at')],
            'rc'                      => ['nullable', 'string', 'max:100'],
            'telephone'               => ['nullable', 'string', 'max:30'],
            'fax'                     => ['nullable', 'string', 'max:30'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'adresse'                 => ['nullable', 'string', 'max:500'],
            'ville'                   => ['nullable', 'string', 'max:100'],
            'region'                  => ['nullable', 'string', 'max:100'],
            'pays'                    => ['nullable', 'string', 'max:100'],
            'representant'            => ['nullable', 'string', 'max:255'],
            'fonction_representant'   => ['nullable', 'string', 'max:255'],
            'domaine_activite_id'     => ['nullable', 'exists:domaines_activite,id'],
            'statut'                  => ['nullable', Rule::in(['actif', 'suspendu', 'blackliste'])],
            'observations'            => ['nullable', 'string'],
            'banques'                 => ['nullable', 'array'],
            'banques.*.banque_id'     => ['required_with:banques', 'exists:banques,id'],
            'banques.*.numero_compte' => ['required_with:banques', 'string', 'max:100'],
            'banques.*.rib'           => ['nullable', 'string', 'max:100'],
            'banques.*.swift'         => ['nullable', 'string', 'max:20'],
            'banques.*.iban'          => ['nullable', 'string', 'max:50'],
            'banques.*.intitule_compte' => ['nullable', 'string', 'max:255'],
            'banques.*.principal'     => ['boolean'],
        ];
    }
}
