<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('GED_UPLOAD') ?? false;
    }

    public function rules(): array
    {
        $maxSize = config('ged.max_file_size', 20480);

        return [
            'documentable_type' => ['required', 'string', Rule::in(array_keys(config('ged.documentable_types', [])))],
            'documentable_id'   => ['required', 'integer', 'min:1'],
            'category'          => ['required', 'string', Rule::in(array_keys(config('ged.categories', [])))],
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:2000'],
            'date_document'     => ['nullable', 'date'],
            'tags'              => ['nullable', 'array'],
            'tags.*'            => ['string', 'max:100'],
            'file'              => ['required', 'file', 'max:'.$maxSize, 'mimes:'.implode(',', config('ged.allowed_mimes', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']))],
        ];
    }

    public function messages(): array
    {
        return [
            'documentable_type.required' => 'Le type d\'entité est obligatoire.',
            'documentable_type.in'       => 'Type d\'entité invalide.',
            'documentable_id.required'   => 'L\'identifiant de l\'entité est obligatoire.',
            'category.required'          => 'La catégorie est obligatoire.',
            'category.in'               => 'Catégorie invalide.',
            'title.required'             => 'Le titre est obligatoire.',
            'file.required'              => 'Le fichier est obligatoire.',
            'file.mimes'                 => 'Types acceptés : PDF, JPG, PNG, DOC, DOCX, XLS, XLSX.',
        ];
    }
}
