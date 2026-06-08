<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Auth;

use App\Enums\TenantType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Registration creates a new tenant and its owning user in one step
 * (SPEC.md §8: "registration and login; tenant creation; role assignment").
 */
class RegisterRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_type' => ['required', Rule::enum(TenantType::class)],
        ];
    }
}
