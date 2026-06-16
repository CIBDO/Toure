<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectOrdreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reject', $this->route('ordre_service')) ?? false;
    }

    public function rules(): array
    {
        return [
            'commentaire_validation' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
