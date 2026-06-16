<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('role');

        return [
            'code'          => ['required', 'string', 'max:50', Rule::unique('roles', 'code')->ignore($id)],
            'libelle'       => ['required', 'string', 'max:255'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'        => 'Le code du rôle est obligatoire.',
            'code.unique'          => 'Ce code de rôle existe déjà.',
            'libelle.required'     => 'Le libellé du rôle est obligatoire.',
            'permissions.*.exists' => 'Une permission sélectionnée est invalide.',
        ];
    }
}
