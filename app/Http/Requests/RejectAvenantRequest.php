<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectAvenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $avenant = $this->route('avenant');

        return $this->user()?->can('reject', $avenant) ?? false;
    }

    public function rules(): array
    {
        return [
            'commentaire_validation' => ['required', 'string', 'max:2000'],
        ];
    }
}
