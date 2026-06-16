<?php

namespace App\Http\Requests;

use App\Models\Avenant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAvenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('avenant')) ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type_avenant', Avenant::TYPE_MONTANT);

        $rules = [
            'type_avenant' => ['sometimes', 'in:montant,delai,objet,mixte'],
            'justification' => ['sometimes', 'string', 'max:10000'],
            'date_signature' => ['sometimes', 'date'],
        ];

        if (in_array($type, [Avenant::TYPE_MONTANT, Avenant::TYPE_MIXTE], true)) {
            $rules['montant_variation'] = ['required', 'integer'];
        }
        if (in_array($type, [Avenant::TYPE_DELAI, Avenant::TYPE_MIXTE], true)) {
            $rules['prolongation_jours'] = ['required', 'integer', 'min:0'];
        }
        if (in_array($type, [Avenant::TYPE_OBJET, Avenant::TYPE_MIXTE], true)) {
            $rules['nouvelle_description_objet'] = ['required', 'string', 'max:5000'];
        }

        return $rules;
    }
}
