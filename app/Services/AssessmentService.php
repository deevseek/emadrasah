<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssessmentComponent;
use App\Models\PredicateRange;
use App\Models\StudentEnrollment;
use App\Models\StudentScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    public function storeScores(AssessmentComponent $component, array $scores, int $userId): void
    {
        DB::transaction(function () use ($component, $scores, $userId): void {
            foreach ($scores as $studentId => $payload) {
                $enrolled = StudentEnrollment::where('student_id', $studentId)
                    ->where('classroom_id', $component->classroom_id)
                    ->where('enrollment_status', 'active')
                    ->exists();

                if (! $enrolled) {
                    throw ValidationException::withMessages([
                        'scores' => 'Siswa dari kelas lain ditolak.',
                    ]);
                }

                foreach (['score', 'remedial_score'] as $field) {
                    $value = $payload[$field] ?? null;

                    if ($value !== null && ($value < 0 || $value > $component->maximum_score)) {
                        throw ValidationException::withMessages([
                            $field => 'Nilai di luar rentang maksimum.',
                        ]);
                    }
                }

                $values = array_filter(
                    [$payload['score'] ?? null, $payload['remedial_score'] ?? null],
                    fn ($value) => $value !== null
                );
                $final = $values === [] ? null : max($values);
                $normalized = $final === null
                    ? null
                    : round(($final / (float) $component->maximum_score) * 100, 2);

                StudentScore::updateOrCreate(
                    [
                        'assessment_component_id' => $component->id,
                        'student_id' => $studentId,
                    ],
                    $payload + [
                        'final_score' => $normalized,
                        'predicate' => $this->predicate($normalized),
                        'entered_by' => $userId,
                    ]
                );
            }

            activity('assessment')
                ->performedOn($component)
                ->causedBy(auth()->user())
                ->event('assessment.scores.saved')
                ->log('Nilai siswa disimpan');
        });
    }

    public function predicate(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }

        return PredicateRange::where('is_active', true)
            ->where('minimum_score', '<=', $score)
            ->where('maximum_score', '>=', $score)
            ->orderBy('sequence')
            ->value('code');
    }

    public function finalScore(
        int $studentId,
        int $classroomId,
        int $subjectId,
        int $semesterId
    ): array {
        $components = AssessmentComponent::where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->get();
        $total = 0.0;
        $complete = true;

        foreach ($components as $component) {
            $score = StudentScore::where('assessment_component_id', $component->id)
                ->where('student_id', $studentId)
                ->first();

            if ($component->is_required && (! $score || $score->final_score === null)) {
                $complete = false;
            }

            if ($score?->final_score !== null) {
                $total += (float) $score->final_score * (float) $component->weight / 100;
            }
        }

        return [
            'score' => round($total, 2),
            'complete' => $complete,
            'predicate' => $this->predicate($total),
        ];
    }
}
