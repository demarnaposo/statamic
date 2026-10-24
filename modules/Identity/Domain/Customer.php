<?php

namespace Modules\Identity\Domain;

use Illuminate\Auth\MustVerifyEmail as VerifiesEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Modules\Identity\Contracts\Data\CustomerData;
use Modules\Identity\Notifications\ResetPasswordNotification;
use Modules\Identity\Notifications\VerifyEmailNotification;

/**
 * Akun customer portal (guard `customer`). Bukan user Statamic.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property ?string $company_name
 * @property ?string $npwp
 * @property ?string $billing_address
 * @property ?Carbon $email_verified_at
 * @property Carbon $privacy_accepted_at
 * @property ?Carbon $marketing_opt_in_at
 */
class Customer extends Authenticatable implements MustVerifyEmail
{
    use HasUlids, Notifiable, VerifiesEmail;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'company_name', 'npwp', 'billing_address',
        'privacy_accepted_at', 'marketing_opt_in_at',
    ];

    protected $hidden = ['password', 'remember_token', 'npwp'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'marketing_opt_in_at' => 'datetime',
            'password' => 'hashed',
            'npwp' => 'encrypted',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function toData(): CustomerData
    {
        return new CustomerData(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            phone: $this->phone,
            companyName: $this->company_name,
            emailVerified: $this->hasVerifiedEmail(),
        );
    }
}
