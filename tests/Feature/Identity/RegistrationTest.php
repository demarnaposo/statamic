<?php

namespace Tests\Feature\Identity;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Modules\Identity\Contracts\Events\CustomerRegistered;
use Modules\Identity\Domain\Customer;
use Modules\Identity\Notifications\VerifyEmailNotification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use CreatesCustomers, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Siti Aminah',
            'email' => ' Siti@Contoh.ID ',
            'phone' => '0812-3456-7890',
            'password' => 'kata-sandi-aman',
            'password_confirmation' => 'kata-sandi-aman',
            'privacy' => true,
            'marketing_opt_in' => false,
        ], $overrides);
    }

    public function test_register_page_renders(): void
    {
        $this->get('/portal/register')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/Register')
                ->where('auth.customer', null)
                ->has('routes.register'));
    }

    public function test_customer_can_register_and_is_sent_verification_email(): void
    {
        Notification::fake();
        Event::fake([CustomerRegistered::class]);

        $this->post('/portal/register', $this->payload())
            ->assertRedirect('/portal/email/verify');

        $customer = Customer::sole();
        $this->assertSame('siti@contoh.id', $customer->email);
        $this->assertSame('081234567890', $customer->phone);
        $this->assertNotNull($customer->privacy_accepted_at);
        $this->assertNull($customer->marketing_opt_in_at);
        $this->assertNull($customer->email_verified_at);
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertGuest('web');

        Notification::assertSentTo($customer, VerifyEmailNotification::class);
        Event::assertDispatched(CustomerRegistered::class, fn ($e) => $e->customerId === $customer->id);
    }

    public function test_privacy_consent_and_valid_data_are_required(): void
    {
        $this->customer(['email' => 'ada@contoh.id']);

        $this->from('/portal/register')->post('/portal/register', $this->payload([
            'privacy' => false,
            'email' => 'ada@contoh.id',
            'phone' => '12345',
            'password_confirmation' => 'beda',
        ]))
            ->assertRedirect('/portal/register')
            ->assertSessionHasErrors(['privacy', 'email', 'phone', 'password']);

        $this->assertSame(
            'Anda harus menyetujui Kebijakan Privasi untuk melanjutkan.',
            session('errors')->first('privacy'),
        );
        $this->assertSame(1, Customer::count());
        $this->assertGuest('customer');
    }

    public function test_unverified_customer_is_redirected_to_verification_notice(): void
    {
        $customer = $this->customer(verified: false);

        $this->actingAs($customer, 'customer')->get('/portal/dashboard')->assertRedirect('/portal/email/verify');
        $this->actingAs($customer, 'customer')->get('/portal/profile')->assertRedirect('/portal/email/verify');
    }

    public function test_register_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/portal/register', $this->payload(['email' => "x{$i}", 'privacy' => false]));
        }

        $this->post('/portal/register', $this->payload())->assertStatus(429);
    }
}
