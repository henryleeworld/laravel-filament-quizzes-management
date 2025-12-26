<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Available quizzes') }}</flux:heading>
    </div>

    @if ($quizzes->isEmpty())
        <flux:callout variant="info">
            {{ __('No quizzes available at this time.') }}
        </flux:callout>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($quizzes as $quiz)
                <div class="flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
                    <div class="flex flex-1 flex-col gap-4">
                        <div class="flex-1">
                            <flux:heading size="lg" class="mb-2">{{ $quiz->title }}</flux:heading>

                            @if ($quiz->description)
                                <flux:text class="mb-4 text-neutral-600 dark:text-neutral-400">
                                    {{ $quiz->description }}
                                </flux:text>
                            @endif

                            <div class="flex flex-wrap gap-2">
                                <flux:badge variant="outline">
                                    {{ $quiz->questions_count }} {{ $quiz->questions_count === 1 ? __('Question') : __('Questions') }}
                                </flux:badge>

                                @if ($quiz->time_limit_minutes)
                                    <flux:badge variant="outline">
                                        {{ $quiz->time_limit_minutes }} {{ __('min') }}
                                    </flux:badge>
                                @endif

                                @if ($quiz->allow_multiple_attempts)
                                    <flux:badge variant="outline">
                                        {{ __('Retakes allowed') }}
                                    </flux:badge>
                                @endif
                            </div>
                        </div>

                        @php
                            $userAttempt = $quiz->attempts->first();
                        @endphp

                        @if ($userAttempt)
                            <div class="rounded-lg border border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-700 dark:bg-neutral-800">
                                <flux:text class="mb-1 text-sm font-medium">{{ __('Last attempt') }}</flux:text>
                                <div class="flex items-center gap-2">
                                    <flux:badge variant="solid" class="bg-blue-600 dark:bg-blue-500">
                                        {{ __('Score: ') . number_format($userAttempt->score, 0) }}%
                                    </flux:badge>
                                    <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">
                                        {{ $userAttempt->submitted_at->diffForHumans() }}
                                    </flux:text>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center gap-2">
                            @if ($this->canAttemptQuiz($quiz))
                                <flux:button
                                    wire:click="startQuiz({{ $quiz->id }})"
                                    variant="primary"
                                    class="w-full"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="startQuiz({{ $quiz->id }})">
                                        {{ $userAttempt ? __('Retake quiz') : __('Start quiz') }}
                                    </span>
                                    <span wire:loading wire:target="startQuiz({{ $quiz->id }})">
                                        {{ __('Loading...') }}
                                    </span>
                                </flux:button>
                            @else
                                <flux:button
                                    variant="ghost"
                                    disabled
                                    class="w-full cursor-not-allowed opacity-50"
                                >
                                    {{ __('Already completed') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
