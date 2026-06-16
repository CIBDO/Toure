<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BanqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('banque');

        return [
            'code'      => ['required', 'string', 'max:20', Rule::unique('banques', 'code')->ignore($id)->whereNull('deleted_at')],
            'libelle'   => ['required', 'string', 'max:255'],
            'sigle'     => ['nullable', 'string', 'max:20'],
            'adresse'   => ['nullable', 'string', 'max:500'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email'     => ['nullable', 'email', 'max:255'],
            'actif'     => ['boolean'],
        ];
    }
}
