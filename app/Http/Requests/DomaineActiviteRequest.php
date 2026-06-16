<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DomaineActiviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('domaine');

        return [
            'code'        => ['required', 'string', 'max:20', Rule::unique('domaines_activite', 'code')->ignore($id)->whereNull('deleted_at')],
            'libelle'     => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'actif'       => ['boolean'],
        ];
    }
}
