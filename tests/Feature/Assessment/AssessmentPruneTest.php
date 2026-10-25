<?php

namespace Tests\Feature\Assessment;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Assessment\Domain\AssessmentAttempt;
use Tests\TestCase;

class AssessmentPruneTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(int $daysAgo, ?string $customerId = null): AssessmentAttempt
    {
        $attempt = AssessmentAttempt::create([
            'token_hash' => hash('sha256', uniqid()), 'version' => 1, 'answers' => [], 'scores' => [], 'recommended_category' => 'pm',
        ]);
        $attempt->forceFill(['customer_id' => $customerId, 'created_at' => now()->subDays($daysAgo)])->save();

        return $attempt;
    }

    public function test_prunes_only_old_unclaimed_attempts(): void
    {
        $oldAnon = $this->attempt(31);
        $oldClaimed = $this->attempt(31, '01JCUSTOMER0000000000000000');
        $recentAnon = $this->attempt(29);

        $this->artisan('model:prune', ['--model' => [AssessmentAttempt::class]])->assertSuccessful();

        $this->assertModelMissing($oldAnon);
        $this->assertModelExists($oldClaimed);
        $this->assertModelExists($recentAnon);
    }

    public function test_prune_is_scheduled_daily(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'model:prune'));

        $this->assertNotNull($event);
        $this->assertStringContainsString('AssessmentAttempt', $event->command);
        $this->assertSame('0 0 * * *', $event->expression);
    }
}
