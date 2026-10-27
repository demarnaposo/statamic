<?php

namespace Modules\Assessment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Assessment\Application\AssessmentRepository;
use Modules\Assessment\Application\AssessmentScorer;
use Modules\Assessment\Http\Requests\ScoreAssessmentRequest;
use Modules\Catalog\Contracts\Data\ProgramSummary;

final class AssessmentController
{
    public function show(AssessmentRepository $repository): JsonResponse
    {
        $definition = $repository->get();

        return $definition
            ? response()->json(['data' => $definition->toPublicArray()])
            : response()->json(['message' => 'Asesmen belum tersedia.'], 404);
    }

    /** Internal (portal): hasil scoring tanpa menyimpan apa pun; portal menyimpan attempt & menambah attempt_token. */
    public function score(ScoreAssessmentRequest $request, AssessmentScorer $scorer): JsonResponse
    {
        $definition = $request->definition();
        $result = $definition ? $scorer->score($definition, $request->answers()) : null;

        if (! $result) {
            return response()->json(['message' => 'Asesmen belum tersedia.'], 404);
        }

        return response()->json(['data' => [
            'version' => $result['version'],
            'scores' => array_map(fn ($s) => ['category' => $s['category']->toArray(), 'score' => $s['score']], $result['scores']),
            'recommended_category' => $result['recommended_category']->toArray(),
            'recommendations' => array_map(fn (ProgramSummary $p) => $p->toArray(), $result['recommendations']),
        ]]);
    }
}
