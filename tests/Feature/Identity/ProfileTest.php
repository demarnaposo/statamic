<?php

namespace Tests\Feature\Identity;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Modules\Identity\Notifications\VerifyEmailNotification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use CreatesCustomers, RefreshDatabase;

    private function profile(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi S.',
            'email' => 'budi@contoh.id',
            'phone' => '+6281299998888',
            'company_name' => 'PT Maju',
            'npwp' => '01.234.567.8-901.000',
            'billing_address' => 'Jl. Merdeka 1, Jakarta',
            'marketing_opt_in' => true,
        ], $overrides);
    }

    public function test_profile_update_stores_encrypted_npwp(): void
    {
        $customer = $this->customer(['email' => 'budi@contoh.id']);

        $this->actingAs($customer, 'customer')->put('/portal/profile', $this->profile())
            ->assertRedirect()->assertSessionHasNoErrors();

        $customer->refresh();
        $this->assertSame('PT Maju', $customer->company_name);
        $this->assertSame('012345678901000', $customer->npwp);
        $this->assertNotNull($customer->marketing_opt_in_at);
        $this->assertNotSame('012345678901000', DB::table('customers')->value('npwp'), 'NPWP terenkripsi di DB');
        $this->assertTrue($customer->hasVerifiedEmail());
    }

    public function test_npwp_only_in_profile_page_props(): void
    {
        $customer = $this->customer(['npwp' => '0123456789012345']);

        $this->actingAs($customer, 'customer')->get('/portal/profile')
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Profile/Edit')->where('profile.npwp', '0123456789012345'));

        $dashboard = $this->actingAs($customer, 'customer')->get('/portal/dashboard')->assertOk();
        $this->assertStringNotContainsString('0123456789012345', $dashboard->getContent());
        $this->assertSame(['name', 'email', 'email_verified'], array_keys($dashboard->inertiaProps('auth.customer')));
    }

    public function test_invalid_npwp_and_phone_are_rejected(): void
    {
        $this->actingAs($this->customer(), 'customer')
            ->put('/portal/profile', $this->profile(['npwp' => '123', 'phone' => 'abc']))
            ->assertSessionHasErrors(['npwp' => 'NPWP harus 15 atau 16 digit angka.', 'phone']);
    }

    public function test_changing_email_requires_reverification(): void
    {
        Notification::fake();
        $customer = $this->customer(['email' => 'lama@contoh.id']);

        $this->actingAs($customer, 'customer')->put('/portal/profile', $this->profile(['email' => 'baru@contoh.id']))
            ->assertRedirect('/portal/email/verify');

        $this->assertFalse($customer->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($customer, VerifyEmailNotification::class);
    }

    public function test_password_change_requires_current_password(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')->put('/portal/password', [
            'current_password' => 'salah', 'password' => 'sandi-baru-123', 'password_confirmation' => 'sandi-baru-123',
        ])->assertSessionHasErrors(['current_password' => 'Kata sandi saat ini salah.']);

        $this->actingAs($customer, 'customer')->put('/portal/password', [
            'current_password' => 'rahasia-123', 'password' => 'sandi-baru-123', 'password_confirmation' => 'sandi-baru-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('sandi-baru-123', $customer->fresh()->password));
    }
}
