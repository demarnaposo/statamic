<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Modules\Catalog\Jobs\TriggerAstroDeploy;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Tests\TestCase;

class DeployHookTest extends TestCase
{
    use CatalogFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCatalog();
        $this->category('pm', 'Manajemen Proyek');
    }

    public function test_content_change_schedules_single_debounced_deploy(): void
    {
        config(['catalog.astro.deploy_hook_url' => 'https://hooks.example.test/build']);
        Bus::fake([TriggerAstroDeploy::class]);

        $program = $this->program('a');
        $program->set('price', 1)->save();
        $this->program('b');

        // queue sync (phpunit.xml) → afterResponse; tiga perubahan dalam jendela debounce = satu deploy.
        Bus::assertDispatchedAfterResponseTimes(TriggerAstroDeploy::class, 1);
    }

    public function test_deploy_is_delayed_on_async_queue(): void
    {
        config(['catalog.astro.deploy_hook_url' => 'https://hooks.example.test/build', 'queue.default' => 'database']);
        Bus::fake([TriggerAstroDeploy::class]);

        $this->program('a');
        $this->program('b');

        Bus::assertDispatchedTimes(TriggerAstroDeploy::class, 1);
        Bus::assertDispatched(TriggerAstroDeploy::class, fn ($job) => $job->delay !== null);
    }

    public function test_many_saves_within_window_push_one_delayed_job_on_database_queue(): void
    {
        config(['catalog.astro.deploy_hook_url' => 'http://127.0.0.1:4322/rebuild?token=t', 'queue.default' => 'database']);
        Queue::fake();

        $program = $this->program('a');
        foreach (range(1, 5) as $i) {
            $this->travel(10)->seconds();
            $program->set('price', 1000 * $i)->save();
        }
        $this->batch($program, 'A-1', '2026-11-01');

        Queue::assertPushed(TriggerAstroDeploy::class, 1);
        Queue::assertPushed(TriggerAstroDeploy::class, fn ($job) => $job->delay !== null
            && now()->subSeconds(50)->addSeconds(60)->equalTo($job->delay));

        // Setelah jendela debounce lewat, perubahan baru menjadwalkan build berikutnya.
        $this->travel(61)->seconds();
        $program->set('price', 1)->save();
        Queue::assertPushed(TriggerAstroDeploy::class, 2);
    }

    public function test_job_failure_is_logged_and_rethrown_for_retry_with_backoff(): void
    {
        config(['catalog.astro.deploy_hook_url' => 'http://127.0.0.1:4322/rebuild?token=rahasia']);
        Http::fake(fn () => throw new ConnectionException('Connection refused'));
        Log::spy();

        $job = new TriggerAstroDeploy;
        $this->assertSame(5, $job->tries);
        $this->assertSame([30, 60, 120, 300], $job->backoff());

        try {
            $job->handle();
            $this->fail('Job harus melempar exception agar di-retry worker.');
        } catch (ConnectionException) {
        }

        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => str_contains($message, 'gagal')
            && $context['hook'] === '127.0.0.1:4322/rebuild'
            && ! str_contains(json_encode($context), 'rahasia'));

        $job->failed(new ConnectionException('Connection refused'));
        Log::shouldHaveReceived('error')->once();
    }

    public function test_no_deploy_without_hook_url_or_for_unrelated_collections(): void
    {
        Bus::fake([TriggerAstroDeploy::class]);
        $this->program('a');
        Bus::assertNothingDispatched();

        config(['catalog.astro.deploy_hook_url' => 'https://hooks.example.test/build']);
        Collection::make('halaman_lain')->save();
        Entry::make()->collection('halaman_lain')->slug('x')->data(['title' => 'X'])->save();
        Bus::assertNothingDispatched();
    }

    public function test_job_posts_to_hook_url(): void
    {
        config(['catalog.astro.deploy_hook_url' => 'https://hooks.example.test/build']);
        Http::fake(['hooks.example.test/*' => Http::response(['ok' => true])]);

        (new TriggerAstroDeploy)->handle();

        Http::assertSent(fn ($request) => $request->url() === 'https://hooks.example.test/build' && $request->method() === 'POST');
    }
}
