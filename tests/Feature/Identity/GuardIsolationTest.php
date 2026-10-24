<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardIsolationTest extends TestCase
{
    use CreatesCustomers, RefreshDatabase;

    public function test_customer_cannot_open_control_panel(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')->get('/cp')->assertRedirect('/cp/auth/login');
        $this->actingAs($customer, 'customer')->get('/cp/collections')->assertRedirect('/cp/auth/login');
        $this->assertGuest('web');
    }

    public function test_cp_login_page_still_renders(): void
    {
        $response = $this->get('/cp/auth/login')->assertOk();

        // Root view & aset milik CP, bukan portal.
        $this->assertStringNotContainsString('resources/js/portal/app.ts', $response->getContent());
        $this->assertStringNotContainsString(asset('build/assets/'), $response->getContent());
        $this->assertStringContainsString('vendor/statamic/cp/build', $response->getContent());
    }

    public function test_statamic_admin_is_not_a_customer(): void
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@contoh.id', 'password' => bcrypt('x'), 'super' => true]);

        $this->actingAs($admin, 'web')->get('/portal/dashboard')->assertRedirect('/portal/login');
        $this->actingAs($admin, 'web')->get('/portal/login')->assertOk();
        $this->assertGuest('customer');
    }

    public function test_cp_dashboard_still_renders_for_admin_alongside_customer_session(): void
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@contoh.id', 'password' => bcrypt('x'), 'super' => true]);

        $html = $this->actingAs($this->customer(), 'customer')->actingAs($admin, 'web')
            ->get('/cp/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('vendor/statamic/cp/build', $html);
        $this->assertStringNotContainsString(asset('build/assets/'), $html);
    }

    public function test_portal_pages_use_portal_root_view(): void
    {
        $html = $this->get('/portal/login')->assertOk()->getContent();

        $this->assertStringContainsString('data-page="app"', $html);
        $this->assertStringContainsString('"component":"Auth\/Login"', $html);
        $this->assertStringNotContainsString('_statamic', $html);
    }
}
