<?php

namespace Tests\Feature\Portal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\Feature\Identity\CreatesCustomers;
use Tests\TestCase;

class PortalPagesTest extends TestCase
{
    use CatalogFixtures, CreatesCustomers, RefreshDatabase;

    public function test_dashboard_without_assessment(): void
    {
        $this->actingAs($this->customer(['name' => 'Rina Wati']), 'customer')->get('/portal/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard')
                ->where('latestAssessment', null)
                ->where('recommendations', [])
                ->where('auth.customer.name', 'Rina Wati')
                ->where('app.landing_url', 'http://localhost:4321'));
    }

    public function test_coming_soon_pages(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'customer')->get('/portal/orders')
            ->assertInertia(fn (AssertableInertia $page) => $page->component('ComingSoon')->where('section', 'orders'));
        $this->actingAs($customer, 'customer')->get('/portal/programs')
            ->assertInertia(fn (AssertableInertia $page) => $page->component('ComingSoon')->where('section', 'programs'));
    }

    public function test_checkout_placeholder_shows_batch_summary(): void
    {
        $this->setUpCatalog();
        $this->category('pm', 'Manajemen Proyek');
        $program = $this->program('pmp', ['title' => 'PMP', 'price' => 4000000]);
        $batch = $this->batch($program, 'PMP-01', '2026-11-10');

        $this->get('/portal/checkout?batch='.$batch->id().'&qty=3')->assertRedirect('/portal/login');

        $this->actingAs($this->customer(), 'customer')->get('/portal/checkout?batch='.$batch->id().'&qty=3')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Checkout/ComingSoon')
                ->where('qty', 3)
                ->where('batch.code', 'PMP-01')
                ->where('batch.price', 4000000)
                ->where('batch.total', 12000000)
                ->where('batch.program.url', 'http://localhost:4321/program/pmp'));
    }

    public function test_checkout_with_unknown_batch(): void
    {
        $this->setUpCatalog();

        $this->actingAs($this->customer(), 'customer')->get('/portal/checkout?batch=tidak-ada&qty=999')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('batch', null)->where('qty', 50));
    }
}
