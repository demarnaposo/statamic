<?php

namespace Modules\Assessment\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $token_hash
 * @property int $version
 * @property array<string, string> $answers
 * @property list<array{category: array{slug: string, title: string, description: ?string}, score: int}> $scores
 * @property string $recommended_category
 * @property ?string $customer_id
 * @property ?Carbon $claimed_at
 * @property Carbon $created_at
 */
class AssessmentAttempt extends Model
{
    use HasUlids;

    protected $fillable = ['token_hash', 'version', 'answers', 'scores', 'recommended_category'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'answers' => 'array',
            'scores' => 'array',
            'claimed_at' => 'datetime',
        ];
    }
}
