<?php

namespace Modules\Assessment\Application;

use Illuminate\Support\Str;
use Modules\Assessment\Contracts\AssessmentResults;
use Modules\Assessment\Contracts\Data\AssessmentResultData;
use Modules\Assessment\Domain\AssessmentAttempt;
use Modules\Catalog\Contracts\Data\Category;

/** Menyimpan attempt anonim (token acak, hanya hash di DB) dan mengklaimnya ke customer. */
final class AttemptStore implements AssessmentResults
{
    /**
     * @param  array<string, string>  $answers
     * @param  array{version: int, scores: list<array{category: Category, score: int}>, recommended_category: Category}  $result
     * @return string attempt_token (plaintext, hanya dikembalikan sekali)
     */
    public function record(array $answers, array $result): string
    {
        $token = Str::random(48);

        AssessmentAttempt::create([
            'token_hash' => self::hash($token),
            'version' => $result['version'],
            'answers' => $answers,
            'scores' => array_map(fn ($s) => ['category' => $s['category']->toArray(), 'score' => $s['score']], $result['scores']),
            'recommended_category' => $result['recommended_category']->slug,
        ]);

        return $token;
    }

    /**
     * Klaim sekali saja: hanya attempt yang belum punya pemilik yang diperbarui (atomik).
     * Token yang tidak dikenal atau sudah diklaim (siapa pun) diabaikan.
     */
    public function claim(string $token, string $customerId): bool
    {
        return AssessmentAttempt::query()
            ->where('token_hash', self::hash($token))
            ->whereNull('customer_id')
            ->update(['customer_id' => $customerId, 'claimed_at' => now(), 'updated_at' => now()]) === 1;
    }

    public function forCustomer(string $customerId): array
    {
        return AssessmentAttempt::query()
            ->where('customer_id', $customerId)
            ->latest()->orderByDesc('id')
            ->get()
            ->map(fn (AssessmentAttempt $a) => new AssessmentResultData(
                id: $a->id,
                version: $a->version,
                recommendedCategorySlug: $a->recommended_category,
                recommendedCategoryTitle: collect($a->scores)->firstWhere('category.slug', $a->recommended_category)['category']['title'] ?? $a->recommended_category,
                scores: array_map(fn ($s) => ['slug' => $s['category']['slug'], 'title' => $s['category']['title'], 'score' => (int) $s['score']], $a->scores),
                takenAt: $a->created_at->toIso8601String(),
            ))
            ->all();
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
