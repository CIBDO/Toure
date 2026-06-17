<?php

namespace App\Http\Requests;

use App\Models\Reception;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reception = $this->route('reception');
        if ($reception instanceof Reception && !$reception->isDraft()) {
            return false;
        }
        return $this->user()?->hasPermission('RECEPTION_EDIT') ?? false;
    }

    public function rules(): array
    {
        return [
            'type_reception' => ['sometimes', 'in:provisoire,partielle,definitive'],
            'date_reception' => ['sometimes', 'date'],
            'lieu_reception' => ['nullable', 'string', 'max:255'],
            'responsable_reception' => ['nullable', 'string', 'max:255'],
            'constatations' => ['nullable', 'string', 'max:10000'],
            'reserves' => ['nullable', 'string', 'max:10000'],
            'statut_conformite' => ['nullable', 'in:conforme,non_conforme,conforme_avec_reserves'],
            'quantite_receptionnee' => ['nullable', 'numeric', 'min:0'],
            'reception_items' => ['nullable', 'array'],
            'reception_items.*.id' => ['nullable', 'integer', 'exists:reception_items,id'],
            'reception_items.*.label' => ['nullable', 'string', 'max:255'],
            'reception_items.*.quantite_prevue' => ['nullable', 'numeric', 'min:0'],
            'reception_items.*.quantite_recue' => ['nullable', 'numeric', 'min:0'],
            'reception_items.*.conforme' => ['nullable', 'boolean'],
            'reception_items.*.observation' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
