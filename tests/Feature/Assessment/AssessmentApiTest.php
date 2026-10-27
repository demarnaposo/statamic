<?php

namespace Tests\Feature\Assessment;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Statamic\Facades\GlobalSet;
use Tests\Feature\Catalog\CatalogFixtures;
use Tests\TestCase;

class AssessmentApiTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    private const SCORE = '/api/v1/internal/assessment/score';

    private const TOKEN = 'token-layanan-portal';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCatalog();
        config(['services.portal.token' => self::TOKEN]);

        $this->category('pm', 'Manajemen Proyek', 1);
        $this->category('sec', 'Keamanan', 2);
        $this->category('data', 'Data', 3);

        $this->program('pm-a', ['title' => 'A PM']);
        $this->program('pm-b', ['title' => 'B PM', 'featured' => true]);
        $this->program('pm-c', ['title' => 'C PM']);
        $this->program('pm-d', ['title' => 'D PM']);
        $this->program('sec-a', ['category' => 'sec']);

        $option = fn ($id, $category) => ['option_id' => $id, 'label' => 'Label '.$id, 'category' => $category];

        GlobalSet::findByHandle('assessment')->inDefaultSite()->data([
            'title' => 'Kuis',
            'intro' => 'Pengantar',
            'version' => 3,
            'questions' => [
                ['question_id' => 'q1', 'question' => 'Satu?', 'options' => [$option('q1a', 'pm'), $option('q1b', 'sec'), $option('q1c', 'data')]],
                ['question_id' => 'q2', 'question' => 'Dua?', 'options' => [$option('q2a', 'pm'), $option('q2b', 'sec')]],
                ['question_id' => 'q3', 'question' => 'Tiga?', 'options' => [$option('q3a', 'sec'), $option('q3b', 'data')]],
            ],
        ])->save();
    }

    public function test_get_assessment_hides_categories(): void
    {
        $response = $this->getJson('/api/v1/assessment')->assertOk();

        $response->assertExactJson(['data' => [
            'version' => 3,
            'title' => 'Kuis',
            'intro' => 'Pengantar',
            'questions' => [
                ['id' => 'q1', 'question' => 'Satu?', 'options' => [['id' => 'q1a', 'label' => 'Label q1a'], ['id' => 'q1b', 'label' => 'Label q1b'], ['id' => 'q1c', 'label' => 'Label q1c']]],
                ['id' => 'q2', 'question' => 'Dua?', 'options' => [['id' => 'q2a', 'label' => 'Label q2a'], ['id' => 'q2b', 'label' => 'Label q2b']]],
                ['id' => 'q3', 'question' => 'Tiga?', 'options' => [['id' => 'q3a', 'label' => 'Label q3a'], ['id' => 'q3b', 'label' => 'Label q3b']]],
            ],
        ]]);
        $this->assertStringNotContainsString('category', $response->getContent());
    }

    public function test_score_recommends_highest_category_with_featured_first(): void
    {
        $response = $this->score(['answers' => ['q1' => 'q1a', 'q2' => 'q2a', 'q3' => 'q3a']])
            ->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $data = $response->json('data');

        // Tanpa attempt_token: attempt disimpan oleh portal.
        $this->assertSame(['version', 'scores', 'recommended_category', 'recommendations'], array_keys($data));
        $this->assertSame(3, $data['version']);
        $this->assertSame([['pm', 2], ['sec', 1], ['data', 0]], array_map(fn ($s) => [$s['category']['slug'], $s['score']], $data['scores']));
        $this->assertSame(['slug' => 'pm', 'title' => 'Manajemen Proyek', 'description' => 'Deskripsi Manajemen Proyek'], $data['recommended_category']);
        $this->assertSame(['pm-b', 'pm-a', 'pm-c'], array_column($data['recommendations'], 'slug'), 'maks 3, featured dulu');
        $this->assertArrayHasKey('effective_price', $data['recommendations'][0]);
    }

    public function test_tie_is_broken_by_category_order(): void
    {
        // pm 1, sec 1, data 1 → seri, kategori pertama (pm) menang.
        $data = $this->score(['answers' => ['q1' => 'q1a', 'q2' => 'q2b', 'q3' => 'q3b']])
            ->assertOk()->json('data');

        $this->assertSame('pm', $data['recommended_category']['slug']);
        $this->assertSame(['pm', 'sec', 'data'], array_map(fn ($s) => $s['category']['slug'], $data['scores']));
    }

    public function test_incomplete_answers_return_422(): void
    {
        $this->score(['answers' => ['q1' => 'q1a']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.q2', 'answers.q3'])
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->score([])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    }

    public function test_invalid_or_unknown_options_return_422(): void
    {
        // Opsi milik pertanyaan lain tidak valid.
        $this->score(['answers' => ['q1' => 'q2a', 'q2' => 'q2a', 'q3' => 'q3a']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.q1']);

        $this->score(['answers' => ['q1' => 'q1a', 'q2' => 'q2a', 'q3' => 'q3a', 'q99' => 'x']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['answers.q99']);
    }

    public function test_internal_score_requires_service_token(): void
    {
        $answers = ['answers' => ['q1' => 'q1a', 'q2' => 'q2a', 'q3' => 'q3a']];

        $this->postJson(self::SCORE, $answers)->assertUnauthorized();
        $this->postJson(self::SCORE, $answers, ['Authorization' => 'Bearer salah'])->assertUnauthorized();

        // Token server kosong = endpoint mati, termasuk untuk bearer kosong.
        config(['services.portal.token' => '']);
        $this->postJson(self::SCORE, $answers, ['Authorization' => 'Bearer '])->assertUnauthorized();
    }

    public function test_internal_score_has_no_cors_and_public_score_is_gone(): void
    {
        $origin = ['Origin' => 'http://localhost:4321'];

        $this->score(['answers' => ['q1' => 'q1a', 'q2' => 'q2a', 'q3' => 'q3a']], $origin)
            ->assertOk()
            ->assertHeaderMissing('Access-Control-Allow-Origin');
        $this->options(self::SCORE, [], $origin + ['Access-Control-Request-Method' => 'POST'])
            ->assertHeaderMissing('Access-Control-Allow-Origin');

        // CORS tetap untuk API publik landing.
        $this->getJson('/api/v1/assessment', $origin)->assertHeader('Access-Control-Allow-Origin', 'http://localhost:4321');

        // Scoring publik pindah ke portal.
        $this->postJson('/api/v1/assessment/score', ['answers' => []])->assertNotFound();
    }

    private function score(array $body, array $headers = []): TestResponse
    {
        return $this->postJson(self::SCORE, $body, ['Authorization' => 'Bearer '.self::TOKEN] + $headers);
    }
}
