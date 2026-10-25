<?php

namespace Tests\Feature\Portal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PortalSessionCookieTest extends TestCase
{
    use RefreshDatabase;

    private function cookie(Response $response, string $name): ?Cookie
    {
        return collect($response->headers->getCookies())->first(fn (Cookie $c) => $c->getName() === $name);
    }

    public function test_portal_uses_own_session_cookie_scoped_to_prefix(): void
    {
        $response = $this->get('/portal/login')->assertOk()->baseResponse;

        $this->assertSame('/portal', $this->cookie($response, config('portal.session_cookie'))?->getPath());
        $this->assertSame('/portal', $this->cookie($response, 'XSRF-TOKEN')?->getPath());
        $this->assertNull($this->cookie($response, 'statamic-session'));
    }

    public function test_cp_keeps_default_session_cookie(): void
    {
        $response = $this->get('/cp/auth/login')->assertOk()->baseResponse;

        $this->assertSame('/', $this->cookie($response, 'statamic-session')?->getPath());
        $this->assertNull($this->cookie($response, config('portal.session_cookie')));
    }

    public function test_portal_domain_without_prefix_scopes_by_host_only(): void
    {
        config(['portal.domain' => 'app.contoh.test', 'portal.prefix' => '']);

        // Urutan: konfigurasi session diubah di dalam proses yang sama (satu request = satu proses di produksi).
        $other = $this->call('GET', 'http://localhost/anything')->baseResponse;
        $portal = $this->call('GET', 'http://app.contoh.test/anything')->baseResponse;

        $this->assertSame('/', $this->cookie($portal, config('portal.session_cookie'))?->getPath());
        $this->assertNull($this->cookie($other, config('portal.session_cookie')));
    }
}
