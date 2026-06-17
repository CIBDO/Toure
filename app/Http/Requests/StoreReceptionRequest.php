<?php

namespace App\Http\Requests;

use App\Models\Reception;
use App\Models\Contrat;
use Illuminate\Foundation\Http\FormRequest;

class StoreReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        $contrat = $this->route('contrat');
        if ($contrat instanceof Contrat && $contrat->statut === 'archived') {
            return false;
        }
        return $user->hasPermission('RECEPTION_CREATE') || $user->hasPermission('CONTRATS_EDIT');
    }

    public function rules(): array
    {
        $rules = [
            'type_reception' => ['required', 'in:provisoire,partielle,definitive'],
            'date_reception' => ['required', 'date'],
            'lieu_reception' => ['nullable', 'string', 'max:255'],
            'responsable_reception' => ['nullable', 'string', 'max:255'],
            'constatations' => ['nullable', 'string', 'max:10000'],
            'reserves' => ['nullable', 'string', 'max:10000'],
            'statut_conformite' => ['nullable', 'in:conforme,non_conforme,conforme_avec_reserves'],
            'quantite_receptionnee' => ['nullable', 'numeric', 'min:0'],
            'reception_items' => ['nullable', 'array'],
            'reception_items.*.label' => ['nullable', 'string', 'max:255'],
            'reception_items.*.quantite_prevue' => ['nullable', 'numeric', 'min:0'],
            'reception_items.*.quantite_recue' => ['nullable', 'numeric', 'min:0'],
            'reception_items.*.conforme' => ['nullable', 'boolean'],
            'reception_items.*.observation' => ['nullable', 'string', 'max:2000'],
        ];

        return $rules;
    }
}
