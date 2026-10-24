<?php

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    use IndonesianMessages;

    protected function prepareForValidation(): void
    {
        $this->normalizeCustomerInput();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:customers,email'],
            'phone' => $this->phoneRules(),
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'privacy' => ['accepted'],
            'marketing_opt_in' => ['boolean'],
        ];
    }
}
