<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('user');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'nom'           => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'prenom'        => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'email'         => [
                $isUpdate ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id)->whereNull('deleted_at'),
            ],
            'telephone'     => ['nullable', 'string', 'max:30'],
            'fonction'      => ['nullable', 'string', 'max:255'],
            'unite_service' => ['nullable', 'string', 'max:255'],
            'region'        => ['nullable', 'string', 'max:100'],
            'password'      => [$isUpdate ? 'nullable' : 'nullable', 'string', 'min:8'],
            'statut'        => ['nullable', Rule::in(['ACTIF', 'SUSPENDU', 'DESACTIVE', 'EN_ATTENTE_ACTIVATION'])],
            'type_compte'   => ['nullable', Rule::in(['CANAM', 'CONTRAT', 'SYSTEME'])],
            'roles'         => ['nullable', 'array'],
            'roles.*'       => ['integer', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'     => 'Le nom est obligatoire.',
            'prenom.required'  => 'Le prénom est obligatoire.',
            'email.required'   => 'L\'email est obligatoire.',
            'email.unique'     => 'Cet email est déjà utilisé.',
            'email.email'      => 'L\'email n\'est pas valide.',
            'password.min'     => 'Le mot de passe doit contenir au moins 8 caractères.',
        ];
    }
}
