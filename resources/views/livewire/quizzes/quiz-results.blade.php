<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Quiz results') }}</flux:heading>
    </div>

    {{-- Score Summary Card --}}
    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-8 dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-col items-center gap-6 md:flex-row md:items-start md:justify-between">
            <div class="flex flex-col items-center gap-4 md:items-start">
                <flux:heading size="lg">{{ $attempt->quiz->title }}</flux:heading>

                <div class="flex items-center gap-4">
                    <div class="text-center">
                        <div class="text-5xl font-bold {{ $attempt->score >= 70 ? 'text-green-600 dark:text-green-500' : 'text-red-600 dark:text-red-500' }}">
                            {{ number_format($attempt->score, 0) }}%
                        </div>
                        <flux:text class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                            {{ __('Your score') }}
                        </flux:text>
                    </div>

                    <div class="h-16 w-px bg-neutral-200 dark:bg-neutral-700"></div>

                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <flux:text>{{ $attempt->correct_count }} {{ __('correct') }}</flux:text>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <flux:text>{{ $attempt->wrong_count }} {{ __('wrong') }}</flux:text>
                        </div>
                    </div>
                </div>

                @if ($attempt->time_taken_seconds)
                    <flux:badge variant="outline">
                        {{ __('Time: ') . gmdate('i:s', $attempt->time_taken_seconds) }}
                    </flux:badge>
                @endif

                @if ($attempt->score >= 70)
                    <flux:badge variant="solid" class="bg-green-600 dark:bg-green-500">
                        {{ __('Passed') }}
                    </flux:badge>
                @else
                    <flux:badge variant="solid" class="bg-red-600 dark:bg-red-500">
                        {{ __('Failed') }}
                    </flux:badge>
                @endif
            </div>

            <div class="flex flex-col gap-2">
                @if ($this->canRetakeQuiz())
                    <flux:button
                        wire:click="retakeQuiz"
                        variant="primary"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="retakeQuiz">
                            {{ __('Retake quiz') }}
                        </span>
                        <span wire:loading wire:target="retakeQuiz">
                            {{ __('Loading...') }}
                        </span>
                    </flux:button>
                @endif

                <flux:button
                    href="{{ route('quizzes.index') }}"
                    variant="ghost"
                    wire:navigate
                >
                    {{ __('Back to quizzes') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Question Breakdown --}}
    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Question breakdown') }}</flux:heading>

        @foreach ($questions as $index => $question)
            @php
                $userAnswer = $attempt->answers->firstWhere('question_id', $question->id);
                $correctOption = $question->options->firstWhere('is_correct', true);
                $isCorrect = $userAnswer?->is_correct ?? false;
            @endphp

            <div class="overflow-hidden rounded-xl border {{ $isCorrect ? 'border-green-200 dark:border-green-800' : 'border-red-200 dark:border-red-800' }} bg-white p-6 dark:bg-neutral-900">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full {{ $isCorrect ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900' }}">
                        @if ($isCorrect)
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        @else
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        @endif
                    </div>

                    <div class="flex-1 space-y-4">
                        <div>
                            <flux:heading size="base" class="mb-2">
                                {{ __('Question') }} {{ $index + 1 }}
                            </flux:heading>
                            <flux:text class="text-neutral-700 dark:text-neutral-300">
                                {{ $question->text }}
                            </flux:text>
                        </div>

                        @if ($question->image_path)
                            <img
                                src="{{ Storage::url($question->image_path) }}"
                                alt="{{ __('Question image') }}"
                                class="max-w-md rounded-lg"
                            >
                        @endif

                        <div class="space-y-2">
                            @foreach ($question->options as $option)
                                <div class="flex items-center gap-2 rounded-lg border p-3
                                    {{ $option->id === $userAnswer?->selected_option_id && $isCorrect ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-950' : '' }}
                                    {{ $option->id === $userAnswer?->selected_option_id && !$isCorrect ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950' : '' }}
                                    {{ $option->is_correct && !$isCorrect ? 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-950' : '' }}
                                    {{ $option->id !== $userAnswer?->selected_option_id && !$option->is_correct ? 'border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800' : '' }}
                                ">
                                    <flux:text class="flex-1">{{ $option->text }}</flux:text>

                                    @if ($option->id === $userAnswer?->selected_option_id)
                                        <flux:badge variant="solid" class="bg-blue-600 dark:bg-blue-500">
                                            {{ __('Your answer') }}
                                        </flux:badge>
                                    @endif

                                    @if ($option->is_correct)
                                        <flux:badge variant="solid" class="bg-green-600 dark:bg-green-500">
                                            {{ __('Correct') }}
                                        </flux:badge>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($question->explanation)
                            <flux:callout variant="info">
                                <strong>{{ __('Explanation: ') }}</strong>{{ $question->explanation }}
                            </flux:callout>
                        @endif

                        @if ($userAnswer?->time_spent)
                            <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                                {{ __('Time spent: ') . gmdate('i:s', $userAnswer->time_spent) }}
                            </flux:text>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Bottom Actions --}}
    <div class="flex items-center justify-center gap-4 border-t border-neutral-200 pt-6 dark:border-neutral-700">
        @if ($this->canRetakeQuiz())
            <flux:button
                wire:click="retakeQuiz"
                variant="primary"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="retakeQuiz">
                    {{ __('Retake quiz') }}
                </span>
                <span wire:loading wire:target="retakeQuiz">
                    {{ __('Loading...') }}
                </span>
            </flux:button>
        @endif

        <flux:button
            href="{{ route('quizzes.index') }}"
            variant="ghost"
            wire:navigate
        >
            {{ __('Back to quizzes') }}
        </flux:button>
    </div>
</div>
