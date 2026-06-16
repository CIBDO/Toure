<?php

namespace App\Http\Requests;

use App\Models\Engagement;
use App\Services\ContractFinanceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('paiement');

        return [
            'engagement_id'          => ['required', 'exists:engagements,id'],
            'reference'              => [
                'required', 'string', 'max:100',
                Rule::unique('paiements', 'reference')->ignore($id)->whereNull('deleted_at'),
            ],
            'date_paiement'          => ['required', 'date'],
            'montant'                => ['required', 'integer', 'min:1'],
            'mode_paiement'          => ['required', Rule::in(['virement', 'cheque', 'espece', 'autre'])],
            'banque_id'              => ['nullable', 'exists:banques,id'],
            'observation'            => ['nullable', 'string'],
            'statut'                 => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected'])],
            'commentaire_validation' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->any()) {
                return;
            }

            $engagementId = $this->input('engagement_id');
            $montant      = (int) $this->input('montant', 0);
            $excludeId    = $this->route('paiement') ? (int) $this->route('paiement') : null;

            $engagement = Engagement::find($engagementId);
            if (!$engagement) {
                return;
            }

            $service = new ContractFinanceService();
            if (!$service->checkPaymentLimit($engagement, $montant, $excludeId)) {
                $v->errors()->add('montant', 'Le montant dépasse le solde disponible de l\'engagement.');
            }
        });
    }
}
