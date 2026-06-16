<?php

namespace App\Http\Requests;

use App\Models\OrdreService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('ordre_service')) ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type_os', OrdreService::TYPE_DEMARRAGE);
        $impact = $this->input('impact_delai', OrdreService::IMPACT_NONE);

        $rules = [
            'type_os' => ['sometimes', 'in:demarrage,suspension,reprise,arret,modification,autre'],
            'objet' => ['sometimes', 'required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'date_emission' => ['sometimes', 'required', 'date'],
            'date_effet' => ['nullable', 'date'],
            'impact_delai' => ['sometimes', 'in:none,extend,reduce'],
            'delai_jours' => ['nullable', 'integer'],
        ];

        if ($type === OrdreService::TYPE_SUSPENSION) {
            $rules['description'] = ['required', 'string', 'max:10000'];
            $rules['date_effet'] = ['required', 'date'];
        }

        if (in_array($impact, [OrdreService::IMPACT_EXTEND, OrdreService::IMPACT_REDUCE], true)) {
            $rules['delai_jours'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }
}
