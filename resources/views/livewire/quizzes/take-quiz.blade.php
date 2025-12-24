<div class="w-full space-y-6"
    x-data="{
        remainingSeconds: @entangle('remainingSeconds'),
        countdown() {
            if (this.remainingSeconds !== null && this.remainingSeconds > 0) {
                this.remainingSeconds--;
            }
            if (this.remainingSeconds === 0) {
                $wire.dispatch('timer-expired');
            }
        },
        formatTime(seconds) {
            if (seconds === null) return '';
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }
    }"
    x-init="if (remainingSeconds !== null) { setInterval(() => countdown(), 1000) }">

    {{-- Header with Timer and Progress --}}
    <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="lg" class="mb-1">{{ $quiz->title }}</flux:heading>
            <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                {{ __('Question') }} {{ ($currentQuestionIndex + 1) . __(' of :count', ['count' => $questions->count()]) }}
            </flux:text>
        </div>

        @if ($quiz->time_limit)
            <div class="flex items-center gap-2">
                <flux:badge
                    :variant="$remainingSeconds !== null && $remainingSeconds < 60 ? 'solid' : 'outline'"
                    class="{{ $remainingSeconds !== null && $remainingSeconds < 60 ? 'bg-red-600 dark:bg-red-500' : '' }}"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="formatTime(remainingSeconds)"></span>
                </flux:badge>
            </div>
        @endif
    </div>

    {{-- Progress Bar --}}
    @php
        $answeredCount = count(array_filter($answers));
        $progressPercentage = $questions->count() > 0 ? ($answeredCount / $questions->count()) * 100 : 0;
    @endphp
    <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
        <div class="mb-2 flex items-center justify-between">
            <flux:text class="text-sm font-medium">{{ __('Progress') }}</flux:text>
            <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                {{ $answeredCount }} / {{ $questions->count() }} {{ __('answered') }}
            </flux:text>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
            <div class="h-full bg-blue-600 transition-all duration-300 dark:bg-blue-500" style="width: {{ $progressPercentage }}%"></div>
        </div>
    </div>

    {{-- Question Display --}}
    <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
        <flux:heading size="md" class="mb-4">
            {{ $this->currentQuestion->text }}
        </flux:heading>

        @if ($this->currentQuestion->image_path)
            <div class="mb-6">
                <img
                    src="{{ asset('storage/' . $this->currentQuestion->image_path) }}"
                    alt="Question image"
                    class="max-h-96 rounded-lg"
                >
            </div>
        @endif

        {{-- Answer Options --}}
        <div class="space-y-3">
            @foreach ($this->currentQuestionOptions as $option)
                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-neutral-200 p-4 transition-colors hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800
                    {{ isset($answers[$this->currentQuestion->id]) && $answers[$this->currentQuestion->id] == $option->id ? 'bg-blue-50 border-blue-500 dark:bg-blue-950 dark:border-blue-500' : '' }}"
                    wire:key="option-{{ $option->id }}"
                >
                    <input
                        type="radio"
                        name="question_{{ $this->currentQuestion->id }}"
                        value="{{ $option->id }}"
                        wire:model.live.debounce.500ms="answers.{{ $this->currentQuestion->id }}"
                        class="mt-1 h-4 w-4 border-neutral-300 text-blue-600 focus:ring-blue-500 dark:border-neutral-600"
                    >
                    <span class="flex-1 text-sm">{{ $option->text }}</span>
                    <span wire:loading wire:target="answers.{{ $this->currentQuestion->id }}" class="text-xs text-neutral-500">
                        {{ __('Saving...') }}
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Question Navigator --}}
    <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
        <flux:heading size="sm" class="mb-4">{{ __('Questions') }}</flux:heading>
        <div class="grid grid-cols-5 gap-2 sm:grid-cols-8 md:grid-cols-10">
            @foreach ($questions as $index => $question)
                <button
                    type="button"
                    wire:click="goToQuestion({{ $index }})"
                    wire:loading.attr="disabled"
                    class="flex h-10 w-10 items-center justify-center rounded-lg border text-sm font-medium transition-colors
                    {{ $currentQuestionIndex === $index
                        ? 'border-blue-500 bg-blue-600 text-white dark:bg-blue-500'
                        : (isset($answers[$question->id])
                            ? 'border-green-500 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-600 dark:bg-green-950 dark:text-green-400'
                            : 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:bg-neutral-800') }}"
                    wire:key="nav-{{ $question->id }}"
                >
                    {{ $index + 1 }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Navigation Buttons --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex gap-2">
            <flux:button
                wire:click="previousQuestion"
                variant="outline"
                :disabled="$currentQuestionIndex === 0"
                wire:loading.attr="disabled"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                {{ __('Previous') }}
            </flux:button>

            @if ($currentQuestionIndex < $questions->count() - 1)
                <flux:button
                    wire:click="nextQuestion"
                    variant="outline"
                    wire:loading.attr="disabled"
                >
                    {{ __('Next') }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </flux:button>
            @endif
        </div>

        <flux:button
            wire:click="submitQuiz"
            variant="primary"
            wire:loading.attr="disabled"
            wire:confirm="{{ __('Are you sure you want to submit this quiz? You cannot change your answers after submission.') }}"
        >
            <span wire:loading.remove wire:target="submitQuiz">
                {{ __('Submit quiz') }}
            </span>
            <span wire:loading wire:target="submitQuiz">
                {{ __('Submitting...') }}
            </span>
        </flux:button>
    </div>
</div>
