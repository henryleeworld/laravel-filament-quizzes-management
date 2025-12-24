<?php

namespace App\Filament\Widgets;

use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalStudents = User::query()
            ->where('is_admin', false)
            ->count();

        $totalQuizzes = Quiz::count();

        $totalAttempts = Attempt::query()
            ->whereNotNull('submitted_at')
            ->count();

        $avgScore = Attempt::query()
            ->whereNotNull('submitted_at')
            ->avg('score') ?? 0;

        return [
            Stat::make(__('Total students'), $totalStudents)
                ->description(__('Registered students'))
                ->color('primary'),
            Stat::make(__('Total quizzes'), $totalQuizzes)
                ->description(__('Available quizzes'))
                ->color('info'),
            Stat::make(__('Total attempts'), $totalAttempts)
                ->description(__('Completed attempts'))
                ->color('success'),
            Stat::make(__('Average score'), number_format($avgScore, 1).'%')
                ->description(__('Across all attempts'))
                ->color('warning'),
        ];
    }
}
