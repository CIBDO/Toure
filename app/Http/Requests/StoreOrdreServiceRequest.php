<?php

namespace App\Http\Requests;

use App\Models\OrdreService;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrdreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', OrdreService::class) ?? false;
    }

    public function rules(): array
    {
        $type = $this->input('type_os', OrdreService::TYPE_DEMARRAGE);
        $impact = $this->input('impact_delai', OrdreService::IMPACT_NONE);

        $rules = [
            'numero' => ['nullable', 'string', 'max:80'],
            'type_os' => ['required', 'in:demarrage,suspension,reprise,arret,modification,autre'],
            'objet' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'date_emission' => ['required', 'date'],
            'date_effet' => ['nullable', 'date'],
            'impact_delai' => ['required', 'in:none,extend,reduce'],
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

    public function messages(): array
    {
        return [
            'objet.required' => 'L\'objet de l\'ordre de service est obligatoire.',
            'date_emission.required' => 'La date d\'émission est obligatoire.',
            'description.required' => 'Le motif/description est obligatoire pour un OS de type suspension.',
            'date_effet.required' => 'La date d\'effet est obligatoire pour une suspension.',
            'delai_jours.required' => 'Le nombre de jours est obligatoire lorsque l\'impact sur le délai est prolongation ou réduction.',
        ];
    }
}
