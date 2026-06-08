<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'document_type_id' => ['required', Rule::exists('document_types', 'id')],
            // entity_id / person_id are constrained to the caller's tenant, so a
            // foreign id cannot be attached even though exists() ignores scopes.
            'entity_id' => [
                'nullable', 'required_without:person_id',
                Rule::exists('entities', 'id')->where('tenant_id', $tenantId),
            ],
            'person_id' => [
                'nullable', 'required_without:entity_id',
                Rule::exists('persons', 'id')->where('tenant_id', $tenantId),
            ],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
