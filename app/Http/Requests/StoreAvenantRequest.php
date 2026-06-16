<?php

namespace App\Http\Requests;

use App\Models\Avenant;
use Illuminate\Foundation\Http\FormRequest;

class StoreAvenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Avenant::class) ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type_avenant', Avenant::TYPE_MONTANT);

        $rules = [
            'type_avenant' => ['required', 'in:montant,delai,objet,mixte'],
            'justification' => ['required', 'string', 'max:10000'],
            'date_signature' => ['required', 'date'],
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
