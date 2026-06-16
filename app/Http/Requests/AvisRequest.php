<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('avi');

        return [
            'reference'          => ['required', 'string', 'max:100', Rule::unique('avis', 'reference')->ignore($id)->whereNull('deleted_at')],
            'objet'              => ['required', 'string', 'max:500'],
            'mode_passation'     => ['required', Rule::in(['AO_OUVERT', 'AO_RESTREINT', 'CONSULTATION', 'GRE_A_GRE', 'ENTENTE_DIRECTE'])],
            'article_pour'       => ['nullable', 'string', 'max:255'],
            'article_relatif'    => ['nullable', 'string', 'max:255'],
            'exercice'           => ['required', 'string', 'size:4'],
            'duree'              => ['required', 'integer', 'min:1'],
            'delai'              => ['nullable', 'integer', 'min:1'],
            'date_limite_depot'  => ['required', 'date'],
            'date_ouverture_plis'=> ['required', 'date', 'after_or_equal:date_limite_depot'],
            'date_publication'   => ['required', 'date'],
            'statut'             => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected', 'published', 'closed', 'cancelled'])],
            'observations'       => ['nullable', 'string'],
            'fournisseurs'       => ['nullable', 'array'],
            'fournisseurs.*'     => ['exists:fournisseurs,id'],
            'items'              => ['nullable', 'array'],
            'items.*.expression_besoin_id' => ['required', 'integer', 'exists:expressions_besoin,id'],
            'items.*.designation'=> ['nullable', 'string', 'max:500'],
            'items.*.description_detaillee' => ['nullable', 'string'],
            'items.*.quantite'   => ['nullable', 'numeric', 'min:0'],
            'items.*.unite'      => ['nullable', 'string', 'max:50'],
            'items.*.lieu'       => ['nullable', 'string', 'max:255'],
        ];
    }
}
