<?php

namespace Tests\Feature\Identity;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Identity\Notifications\ResetPasswordNotification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use CreatesCustomers, RefreshDatabase;

    public function test_full_reset_flow(): void
    {
        Notification::fake();
        $customer = $this->customer(['email' => 'budi@contoh.id']);

        $this->get('/portal/forgot-password')->assertOk();
        $this->post('/portal/forgot-password', ['email' => 'budi@contoh.id'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($customer, ResetPasswordNotification::class, function ($n) use (&$token, $customer) {
            $token = $n->token;
            $this->assertStringContainsString('/portal/reset-password/'.$token, $n->toMail($customer)->viewData['actionUrl']);

            return true;
        });

        $this->get('/portal/reset-password/'.$token.'?email=budi@contoh.id')->assertOk();

        $this->post('/portal/reset-password', [
            'token' => $token,
            'email' => 'budi@contoh.id',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertRedirect('/portal/login');

        $this->assertTrue(Hash::check('sandi-baru-123', $customer->fresh()->password));
    }

    public function test_unknown_email_gets_same_message(): void
    {
        Notification::fake();

        $this->post('/portal/forgot-password', ['email' => 'tidak-ada@contoh.id'])
            ->assertSessionHas('status', 'Jika email tersebut terdaftar, tautan untuk mengatur ulang kata sandi telah dikirim.');
        Notification::assertNothingSent();
    }

    public function test_invalid_token_is_rejected(): void
    {
        $customer = $this->customer(['email' => 'budi@contoh.id']);

        $this->post('/portal/reset-password', [
            'token' => 'palsu', 'email' => 'budi@contoh.id', 'password' => 'sandi-baru-123', 'password_confirmation' => 'sandi-baru-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('rahasia-123', $customer->fresh()->password));
    }

    public function test_admin_password_broker_is_untouched(): void
    {
        $this->assertSame('users', config('auth.passwords.users.provider'));
        $this->assertSame('customers', config('auth.passwords.customers.provider'));
        $this->assertSame('customer_password_reset_tokens', config('auth.passwords.customers.table'));
    }
}
