<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Corrections a human may apply while confirming a document. All optional —
 * the human is verifying the extracted suggestions and may adjust any of them.
 */
class ConfirmDocumentRequest extends FormRequest
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
            'document_type_id' => ['sometimes', Rule::exists('document_types', 'id')],
            'entity_id' => ['sometimes', 'nullable', Rule::exists('entities', 'id')->where('tenant_id', $tenantId)],
            'person_id' => ['sometimes', 'nullable', Rule::exists('persons', 'id')->where('tenant_id', $tenantId)],
            'issue_date' => ['sometimes', 'nullable', 'date'],
            'expiry_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
