<?php

namespace App\Http\Requests;

use App\Models\Reception;
use Illuminate\Foundation\Http\FormRequest;

class RejectReceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('RECEPTION_APPROVE') ?? false;
    }

    public function rules(): array
    {
        return [
            'commentaire_validation' => ['required', 'string', 'max:5000'],
        ];
    }
}
