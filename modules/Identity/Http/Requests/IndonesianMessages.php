<?php

namespace Modules\Identity\Http\Requests;

/**
 * Pesan validasi Bahasa Indonesia khusus portal (tidak memakai lang/id global agar
 * pesan validasi CP Statamic tidak ikut berubah).
 */
trait IndonesianMessages
{
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute tidak valid.',
            'email' => 'Format :attribute tidak valid.',
            'max' => ':Attribute maksimal :max karakter.',
            'min' => ':Attribute minimal :min karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'unique' => ':Attribute sudah terdaftar.',
            'accepted' => ':Attribute wajib disetujui.',
            'boolean' => ':Attribute tidak valid.',
            'regex' => 'Format :attribute tidak valid.',
            'current_password' => 'Kata sandi saat ini salah.',
            'phone.regex' => 'Nomor telepon tidak valid (contoh: 081234567890 atau +6281234567890).',
            'npwp.regex' => 'NPWP harus 15 atau 16 digit angka.',
            'privacy.accepted' => 'Anda harus menyetujui Kebijakan Privasi untuk melanjutkan.',
            'password.min' => 'Kata sandi minimal :min karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'phone' => 'nomor telepon',
            'password' => 'kata sandi',
            'current_password' => 'kata sandi saat ini',
            'company_name' => 'nama perusahaan',
            'npwp' => 'NPWP',
            'billing_address' => 'alamat penagihan',
            'privacy' => 'persetujuan kebijakan privasi',
        ];
    }

    protected function normalizeCustomerInput(): void
    {
        $data = [];

        if (is_string($email = $this->input('email'))) {
            $data['email'] = mb_strtolower(trim($email));
        }
        if (is_string($phone = $this->input('phone'))) {
            $data['phone'] = preg_replace('/[\s\-.()]/', '', $phone);
        }
        if (is_string($npwp = $this->input('npwp'))) {
            $digits = preg_replace('/[\s\-.]/', '', $npwp);
            $data['npwp'] = $digits === '' ? null : $digits;
        }

        $this->merge($data);
    }

    /** Nomor HP Indonesia: 08…, 628…, atau +628… */
    protected function phoneRules(): array
    {
        return ['required', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'];
    }
}
