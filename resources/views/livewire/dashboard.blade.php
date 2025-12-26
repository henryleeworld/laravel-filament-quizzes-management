<div class="w-full space-y-6">
    <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>

    {{-- Stats Cards --}}
    <div class="grid gap-4 md:grid-cols-3">
        {{-- Total Attempts --}}
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('Total attempts') }}
                    </flux:text>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ $this->stats->totalAttempts }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Average Score --}}
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                </div>
                <div>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('Average score') }}
                    </flux:text>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ $this->stats->avgScore }}%
                    </div>
                </div>
            </div>
        </div>

        {{-- Pass Rate --}}
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        {{ __('Pass rate') }}
                    </flux:text>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ $this->stats->passRate }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Attempt History --}}
    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
        <div class="border-b border-neutral-200 px-6 py-4 dark:border-neutral-700">
            <flux:heading size="lg">{{ __('Attempt history') }}</flux:heading>
        </div>

        @if ($this->attempts->isEmpty())
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <flux:heading size="base" class="mt-4">{{ __('No attempts yet') }}</flux:heading>
                <flux:text class="mt-2 text-neutral-600 dark:text-neutral-400">
                    {{ __('Start taking quizzes to see your results here.') }}
                </flux:text>
                <div class="mt-6">
                    <flux:button href="{{ route('quizzes.index') }}" variant="primary" wire:navigate>
                        {{ __('Browse quizzes') }}
                    </flux:button>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-neutral-50 dark:bg-neutral-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('Quiz') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('Score') }}
                            </th>
                            <th class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 md:table-cell">
                                {{ __('Correct/Wrong') }}
                            </th>
                            <th class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 md:table-cell">
                                {{ __('Time') }}
                            </th>
                            <th class="hidden px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400 sm:table-cell">
                                {{ __('Date') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('Status') }}
                            </th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($this->attempts as $attempt)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="font-medium text-neutral-900 dark:text-white">
                                        {{ $attempt->quiz->title }}
                                    </flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="font-semibold {{ $attempt->score >= 70 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ number_format($attempt->score, 0) }}%
                                    </span>
                                </td>
                                <td class="hidden whitespace-nowrap px-6 py-4 md:table-cell">
                                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                                        {{ $attempt->correct_count }}/{{ $attempt->wrong_count }}
                                    </flux:text>
                                </td>
                                <td class="hidden whitespace-nowrap px-6 py-4 md:table-cell">
                                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                                        {{ $attempt->time_taken_seconds ? gmdate('i:s', $attempt->time_taken_seconds) : '-' }}
                                    </flux:text>
                                </td>
                                <td class="hidden whitespace-nowrap px-6 py-4 sm:table-cell">
                                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                                        {{ $attempt->submitted_at->format('M j, Y') }}
                                    </flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($attempt->score >= 70)
                                        <flux:badge variant="solid" class="bg-green-600 dark:bg-green-500">
                                            {{ __('Passed') }}
                                        </flux:badge>
                                    @else
                                        <flux:badge variant="solid" class="bg-red-600 dark:bg-red-500">
                                            {{ __('Failed') }}
                                        </flux:badge>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <flux:button
                                        href="{{ route('attempts.results', $attempt) }}"
                                        variant="ghost"
                                        size="sm"
                                        wire:navigate
                                    >
                                        {{ __('View') }}
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($this->attempts->hasPages())
                <div class="border-t border-neutral-200 px-6 py-4 dark:border-neutral-700">
                    {{ $this->attempts->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Category Performance --}}
    @if ($this->categoryPerformance->isNotEmpty())
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
            <div class="border-b border-neutral-200 px-6 py-4 dark:border-neutral-700">
                <flux:heading size="lg">{{ __('Category performance') }}</flux:heading>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-neutral-50 dark:bg-neutral-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('Category') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('Questions answered') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                {{ __('% correct') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($this->categoryPerformance as $category)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="font-medium text-neutral-900 dark:text-white">
                                        {{ $category->name }}
                                    </flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="text-neutral-600 dark:text-neutral-400">
                                        {{ $category->total_answered }}
                                    </flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-24 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                            <div
                                                class="h-full rounded-full {{ $category->percent_correct >= 70 ? 'bg-green-500' : ($category->percent_correct >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                                                style="width: {{ $category->percent_correct }}%"
                                            ></div>
                                        </div>
                                        <span class="font-semibold {{ $category->percent_correct >= 70 ? 'text-green-600 dark:text-green-400' : ($category->percent_correct >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                            {{ $category->percent_correct }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
