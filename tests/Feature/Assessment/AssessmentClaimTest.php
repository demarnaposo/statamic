<?php

namespace Tests\Feature\Assessment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Modules\Assessment\Contracts\AssessmentResults;
use Modules\Assessment\Domain\AssessmentAttempt;
use Statamic\Facades\GlobalSet;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\Feature\Identity\CreatesCustomers;
use Tests\TestCase;

class AssessmentClaimTest extends TestCase
{
    use CatalogFixtures, CreatesCustomers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCatalog();

        $this->category('pm', 'Manajemen Proyek', 1);
        $this->category('sec', 'Keamanan', 2);
        $this->program('pm-a', ['title' => 'A PM']);
        $this->program('sec-a', ['category' => 'sec', 'title' => 'Sec A']);

        $option = fn ($id, $category) => ['option_id' => $id, 'label' => 'Label '.$id, 'category' => $category];
        GlobalSet::findByHandle('assessment')->inDefaultSite()->data([
            'title' => 'Kuis', 'intro' => 'Pengantar', 'version' => 2,
            'questions' => [
                ['question_id' => 'q1', 'question' => 'Satu?', 'options' => [$option('q1a', 'pm'), $option('q1b', 'sec')]],
                ['question_id' => 'q2', 'question' => 'Dua?', 'options' => [$option('q2a', 'pm'), $option('q2b', 'sec')]],
            ],
        ])->save();
    }

    private function score(array $answers = ['q1' => 'q1b', 'q2' => 'q2b']): string
    {
        return $this->postJson('/api/v1/assessment/score', ['answers' => $answers])->assertOk()->json('data.attempt_token');
    }

    private function register(string $email, string $url = '/portal/register'): void
    {
        $this->post($url, [
            'name' => 'Peserta', 'email' => $email, 'phone' => '081234567890',
            'password' => 'kata-sandi-aman', 'password_confirmation' => 'kata-sandi-aman', 'privacy' => true,
        ])->assertRedirect('/portal/email/verify');
    }

    public function test_score_stores_attempt_with_hashed_token_only(): void
    {
        $token = $this->score();

        $attempt = AssessmentAttempt::sole();
        $this->assertSame(hash('sha256', $token), $attempt->token_hash);
        $this->assertSame(['q1' => 'q1b', 'q2' => 'q2b'], $attempt->answers);
        $this->assertSame('sec', $attempt->recommended_category);
        $this->assertSame(2, $attempt->version);
        $this->assertNull($attempt->customer_id);
        $this->assertSame(0, DB::table('assessment_attempts')->where('token_hash', $token)->count());
    }

    public function test_anonymous_attempt_is_claimed_on_register(): void
    {
        $token = $this->score();

        $this->get('/portal/assessment/claim?token='.$token)->assertRedirect('/portal/register');
        $this->get('/portal/register')->assertInertia(fn (AssertableInertia $page) => $page->where('assessment.pending', true));

        $this->register('baru@contoh.id');

        $customerId = auth('customer')->id();
        $attempt = AssessmentAttempt::sole();
        $this->assertSame($customerId, $attempt->customer_id);
        $this->assertNotNull($attempt->claimed_at);
        $this->assertFalse(session()->has('assessment.pending_token'));

        $results = app(AssessmentResults::class)->forCustomer($customerId);
        $this->assertCount(1, $results);
        $this->assertSame('sec', $results[0]->recommendedCategorySlug);
        $this->assertSame('Keamanan', $results[0]->recommendedCategoryTitle);
    }

    public function test_query_parameter_on_login_claims_after_login(): void
    {
        $customer = $this->customer(['email' => 'budi@contoh.id']);
        $token = $this->score();

        $this->get('/portal/login?assessment='.$token)->assertOk();
        $this->post('/portal/login', ['email' => 'budi@contoh.id', 'password' => 'rahasia-123'])
            ->assertRedirect('/portal/dashboard')
            ->assertSessionHas('assessment_claimed', true);

        $this->assertSame($customer->id, AssessmentAttempt::sole()->customer_id);

        $this->get('/portal/dashboard')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('latestAssessment.recommended_category.slug', 'sec')
            ->where('recommendations.0.slug', 'sec-a')
            ->where('assessment.claimed', true));
    }

    public function test_logged_in_customer_claims_directly(): void
    {
        $customer = $this->customer();
        $token = $this->score();

        $this->actingAs($customer, 'customer')->get('/portal/assessment/claim?token='.$token)->assertRedirect('/portal/dashboard');

        $this->assertSame($customer->id, AssessmentAttempt::sole()->customer_id);
    }

    public function test_reused_token_does_not_change_owner(): void
    {
        $owner = $this->customer();
        $token = $this->score();
        $this->actingAs($owner, 'customer')->get('/portal/assessment/claim?token='.$token);
        $claimedAt = AssessmentAttempt::sole()->claimed_at;

        // Customer lain mencoba token yang sama: lewat claim URL, ?assessment=, dan saat daftar.
        $other = $this->customer();
        $this->actingAs($other, 'customer')->get('/portal/assessment/claim?token='.$token)->assertRedirect('/portal/dashboard');
        $this->actingAs($other, 'customer')->get('/portal/dashboard?assessment='.$token)->assertOk();
        auth('customer')->logout();
        $this->register('ketiga@contoh.id', '/portal/register?assessment='.$token);

        $attempt = AssessmentAttempt::sole();
        $this->assertSame($owner->id, $attempt->customer_id);
        $this->assertEquals($claimedAt, $attempt->claimed_at);
        $this->assertSame([], app(AssessmentResults::class)->forCustomer($other->id));
    }

    public function test_unknown_or_malformed_token_is_ignored(): void
    {
        $this->get('/portal/assessment/claim?token=<script>')->assertRedirect('/portal/register');
        $this->assertFalse(session()->has('assessment.pending_token'));

        $this->get('/portal/assessment/claim?token='.str_repeat('a', 48))->assertRedirect('/portal/register');
        $this->register('baru@contoh.id');

        $this->assertSame(0, AssessmentAttempt::count());
    }

    public function test_history_page_lists_results(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'customer')->get('/portal/assessment/claim?token='.$this->score());
        $this->actingAs($customer, 'customer')->get('/portal/assessment/claim?token='.$this->score(['q1' => 'q1a', 'q2' => 'q2a']));

        $this->actingAs($customer, 'customer')->get('/portal/assessments')
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Assessments/Index')->has('results', 2));
    }
}
