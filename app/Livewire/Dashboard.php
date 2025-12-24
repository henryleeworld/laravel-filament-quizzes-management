<?php

namespace App\Livewire;

use App\Models\Attempt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public function getStatsProperty(): object
    {
        $stats = Attempt::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('submitted_at')
            ->selectRaw('
                COUNT(*) as total_attempts,
                COALESCE(AVG(score), 0) as avg_score,
                SUM(CASE WHEN score >= 70 THEN 1 ELSE 0 END) as passed_count
            ')
            ->first();

        $passRate = $stats->total_attempts > 0
            ? ($stats->passed_count / $stats->total_attempts) * 100
            : 0;

        return (object) [
            'totalAttempts' => (int) $stats->total_attempts,
            'avgScore' => round((float) $stats->avg_score, 1),
            'passRate' => round($passRate, 1),
        ];
    }

    public function getAttemptsProperty(): LengthAwarePaginator
    {
        return Attempt::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('submitted_at')
            ->with('quiz:id,title')
            ->latest('submitted_at')
            ->paginate(10);
    }

    public function getCategoryPerformanceProperty(): Collection
    {
        return DB::table('attempt_answers')
            ->join('attempts', 'attempt_answers.attempt_id', '=', 'attempts.id')
            ->join('questions', 'attempt_answers.question_id', '=', 'questions.id')
            ->join('categories', 'questions.category_id', '=', 'categories.id')
            ->where('attempts.user_id', auth()->id())
            ->whereNotNull('attempts.submitted_at')
            ->whereNotNull('attempt_answers.is_correct')
            ->groupBy('categories.id', 'categories.name')
            ->select([
                'categories.name',
                DB::raw('COUNT(*) as total_answered'),
                DB::raw('SUM(attempt_answers.is_correct) as correct_count'),
            ])
            ->orderBy('categories.name')
            ->get()
            ->map(function ($category) {
                $category->percent_correct = $category->total_answered > 0
                    ? round(($category->correct_count / $category->total_answered) * 100, 1)
                    : 0;

                return $category;
            });
    }

    public function render()
    {
        return view('livewire.dashboard')
            ->layout('components.layouts.app', ['title' => __('Dashboard')]);
    }
}
