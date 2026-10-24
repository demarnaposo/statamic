<?php

namespace Tests\Feature\Identity;

use Modules\Identity\Domain\Customer;

trait CreatesCustomers
{
    protected function customer(array $attributes = [], bool $verified = true): Customer
    {
        $customer = Customer::create(array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi'.uniqid().'@contoh.id',
            'phone' => '081234567890',
            'password' => 'rahasia-123',
            'privacy_accepted_at' => now(),
        ], $attributes));

        if ($verified) {
            $customer->forceFill(['email_verified_at' => now()])->save();
        }

        return $customer;
    }
}
