<?php

namespace Modules\Assessment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Assessment\Application\AssessmentDefinition;
use Modules\Assessment\Application\AssessmentRepository;

final class ScoreAssessmentRequest extends FormRequest
{
    private ?AssessmentDefinition $definition = null;

    public function authorize(): bool
    {
        return true;
    }

    public function definition(): ?AssessmentDefinition
    {
        return $this->definition ??= app(AssessmentRepository::class)->get();
    }

    public function rules(): array
    {
        $rules = ['answers' => ['required', 'array']];

        foreach ($this->definition()?->questions ?? [] as $question) {
            $rules['answers.'.$question['id']] = [
                'required',
                'string',
                Rule::in(array_column($question['options'], 'id')),
            ];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $answers = $this->input('answers');

            if (! is_array($answers)) {
                return;
            }

            $known = array_column($this->definition()?->questions ?? [], 'id');

            foreach (array_diff(array_keys($answers), $known) as $unknown) {
                $validator->errors()->add('answers.'.$unknown, 'Pertanyaan tidak dikenal.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Jawaban wajib diisi.',
            'answers.array' => 'Format jawaban tidak valid.',
            'answers.*.required' => 'Pertanyaan ini wajib dijawab.',
            'answers.*.string' => 'Pilihan jawaban tidak valid.',
            'answers.*.in' => 'Pilihan jawaban tidak valid.',
        ];
    }

    /** @return array<string, string> */
    public function answers(): array
    {
        return array_map('strval', (array) $this->validated('answers'));
    }
}
