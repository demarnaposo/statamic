<?php

namespace Tests\Feature\Identity;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Modules\Identity\Notifications\VerifyEmailNotification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use CreatesCustomers, RefreshDatabase;

    public function test_notice_page_renders_for_unverified_customer(): void
    {
        $this->actingAs($this->customer(verified: false), 'customer')
            ->get('/portal/email/verify')->assertOk();
    }

    public function test_signed_link_verifies_email(): void
    {
        Event::fake([Verified::class]);
        $customer = $this->customer(verified: false);

        $url = VerifyEmailNotification::url($customer);
        $this->assertStringContainsString('/portal/email/verify/'.$customer->id.'/', $url);

        $this->actingAs($customer, 'customer')->get($url)
            ->assertRedirect('/portal/dashboard')
            ->assertSessionHas('status', 'Email Anda berhasil diverifikasi.');

        $this->assertTrue($customer->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_tampered_or_foreign_link_is_rejected(): void
    {
        $customer = $this->customer(verified: false);
        $other = $this->customer(verified: false);

        $this->actingAs($customer, 'customer')->get(VerifyEmailNotification::url($customer).'x')->assertForbidden();
        $this->actingAs($customer, 'customer')->get(VerifyEmailNotification::url($other))->assertForbidden();

        $bad = URL::temporarySignedRoute('portal.verification.verify', now()->addHour(), ['id' => $customer->id, 'hash' => sha1('lain@contoh.id')]);
        $this->actingAs($customer, 'customer')->get($bad)->assertForbidden();

        $this->assertFalse($customer->fresh()->hasVerifiedEmail());
    }

    public function test_resend_is_throttled(): void
    {
        Notification::fake();
        $customer = $this->customer(verified: false);

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($customer, 'customer')->post('/portal/email/verification-notification')->assertRedirect();
        }
        $this->actingAs($customer, 'customer')->post('/portal/email/verification-notification')->assertStatus(429);

        Notification::assertSentToTimes($customer, VerifyEmailNotification::class, 6);
    }

    public function test_verification_email_is_indonesian(): void
    {
        $customer = $this->customer(['name' => 'Rina'], verified: false);
        $mail = (new VerifyEmailNotification)->toMail($customer);

        $this->assertSame('Verifikasi alamat email Anda', $mail->subject);
        $html = (string) $mail->render();
        $this->assertStringContainsString('Halo, Rina', $html);
        $this->assertStringContainsString('Verifikasi email', $html);
    }
}
