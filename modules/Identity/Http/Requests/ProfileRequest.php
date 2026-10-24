<?php

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ProfileRequest extends FormRequest
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
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('customers', 'email')->ignore($this->user('customer')->getKey())],
            'phone' => $this->phoneRules(),
            'company_name' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'regex:/^[0-9]{15,16}$/'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'marketing_opt_in' => ['boolean'],
        ];
    }
}
