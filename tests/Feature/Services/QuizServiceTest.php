<?php

namespace Tests\Feature\Services;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use App\Services\QuizService;
use Exception;
use Tests\TestCase;

class QuizServiceTest extends TestCase
{
    protected QuizService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuizService();
    }

    public function test_can_user_attempt_quiz_returns_true_when_multiple_attempts_allowed()
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->assertTrue(
            $this->service->canUserAttemptQuiz($user, $quiz)
        );
    }

    public function test_can_user_attempt_quiz_returns_true_when_no_completed_attempts()
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => false]);

        $this->assertTrue(
            $this->service->canUserAttemptQuiz($user, $quiz)
        );
    }

    public function test_can_user_attempt_quiz_returns_false_when_completed_and_no_retakes()
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => false]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        $this->assertFalse(
            $this->service->canUserAttemptQuiz($user, $quiz)
        );
    }

    public function test_start_quiz_creates_new_attempt()
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => true]);

        $attempt = $this->service->startQuiz($user, $quiz);

        $this->assertInstanceOf(Attempt::class, $attempt);
        $this->assertEquals($user->id, $attempt->user_id);
        $this->assertEquals($quiz->id, $attempt->quiz_id);
        $this->assertNotNull($attempt->started_at);
        $this->assertNull($attempt->submitted_at);

        $this->assertDatabaseHas('attempts', [
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
        ]);
    }

    public function test_start_quiz_throws_exception_when_not_allowed()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User is not allowed to attempt this quiz.');

        $user = User::factory()->create();
        $quiz = Quiz::factory()->create(['allow_multiple_attempts' => false]);

        Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => now(),
        ]);

        $this->service->startQuiz($user, $quiz);
    }

    public function test_get_in_progress_attempt_returns_attempt()
    {
        $user = User::factory()->create();
        $quiz = Quiz::factory()->create();

        $attempt = Attempt::factory()->create([
            'user_id' => $user->id,
            'quiz_id' => $quiz->id,
            'submitted_at' => null,
        ]);

        $result = $this->service->getInProgressAttempt($user, $quiz);

        $this->assertInstanceOf(Attempt::class, $result);
        $this->assertEquals($attempt->id, $result->id);
    }

    public function test_prepare_quiz_questions_respects_shuffle_setting()
    {
        $quiz = Quiz::factory()->create(['shuffle_questions' => false]);
        $questions = Question::factory()->count(5)->create();
        $quiz->questions()->attach($questions->pluck('id'));

        $result = $this->service->prepareQuizQuestions($quiz->fresh());

        $this->assertEquals(
            $questions->pluck('id')->toArray(),
            $result->pluck('id')->toArray()
        );
    }

    public function test_shuffle_question_options_removes_is_correct_flag()
    {
        $quiz = Quiz::factory()->create(['shuffle_answers' => false]);
        $question = Question::factory()->create();

        $options = QuestionOption::factory()->count(4)->create([
            'question_id' => $question->id,
        ]);

        $options->first()->update(['is_correct' => true]);

        $result = $this->service->shuffleQuestionOptions($quiz, $options);

        foreach ($result as $option) {
            $this->assertFalse(property_exists($option, 'is_correct'));
        }
    }

    public function test_save_answer_creates_new_record()
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->create();
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $quiz->questions()->attach($question->id);

        $attempt = Attempt::factory()->create([
            'quiz_id' => $quiz->id,
        ]);

        $this->service->saveAnswer($attempt, $question->id, $option->id);

        $this->assertDatabaseHas('attempt_answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $option->id,
        ]);
    }

    public function test_submit_quiz_updates_score_and_counts()
    {
        $quiz = Quiz::factory()->create(['time_limit_minutes' => null]);
        $question = Question::factory()->create();

        $correct = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $quiz->questions()->attach($question->id);

        $attempt = Attempt::factory()->create([
            'quiz_id' => $quiz->id,
            'started_at' => now()->subMinutes(5),
        ]);

        AttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $correct->id,
        ]);

        $this->service->submitQuiz($attempt);

        $attempt->refresh();

        $this->assertNotNull($attempt->submitted_at);
        $this->assertEquals('100.00', $attempt->score);
        $this->assertEquals(1, $attempt->correct_count);
        $this->assertEquals(0, $attempt->wrong_count);
    }

    public function test_calculate_score_updates_is_correct_field()
    {
        $quiz = Quiz::factory()->create();
        $question = Question::factory()->create();

        $correct = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $quiz->questions()->attach($question->id);

        $attempt = Attempt::factory()->create([
            'quiz_id' => $quiz->id,
        ]);

        $answer = AttemptAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $correct->id,
        ]);

        $this->service->calculateScore($attempt);

        $this->assertTrue($answer->fresh()->is_correct);
    }
}
