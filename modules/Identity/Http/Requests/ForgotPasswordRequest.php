<?php

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
    use IndonesianMessages;

    protected function prepareForValidation(): void
    {
        $this->normalizeCustomerInput();
    }

    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email']];
    }
}
