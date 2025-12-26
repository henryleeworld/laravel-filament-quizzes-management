<?php

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Category;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamPlatformSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $this->command->info(__('Seeding users...'));
        $users = $this->seedUsers();

        $this->command->info(__('Seeding categories...'));
        $categories = $this->seedCategories();

        $this->command->info(__('Seeding questions with options...'));
        $questions = $this->seedQuestions($categories);

        $this->command->info(__('Seeding quizzes...'));
        $quizzes = $this->seedQuizzes($questions);

        $this->command->info(__('Seeding attempts and answers...'));
        $this->seedAttempts($users, $quizzes);

        $this->command->info(__('Exam platform seeded successfully!'));
    }

    private function seedUsers(): object
    {
        $adminUsers = User::factory()->count(2)->admin()->create([
            'email_verified_at' => now(),
        ]);

        $adminUsers[0]->update(['email' => 'admin@admin.com', 'name' => __('Administrator')]);
        $adminUsers[1]->update(['email' => 'manager@admin.com', 'name' => __('Manager')]);

        $studentUsers = User::factory()->count(18)->create([
            'email_verified_at' => now(),
        ]);

        return (object) [
            'admins' => $adminUsers,
            'students' => $studentUsers,
            'all' => $adminUsers->merge($studentUsers),
        ];
    }

    private function seedCategories()
    {
        $categoryNames = [
            'Programming concepts',
            'Database design',
            'Web development',
            'Security',
            'Algorithms',
            'Testing',
            'DevOps',
            'Project management',
        ];

        return collect($categoryNames)->map(function ($name) {
            return Category::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name, language: app()->getLocale()),
            ]);
        });
    }

    private function seedQuestions($categories)
    {
        $allQuestions = collect();

        foreach ($categories as $category) {
            $easyQuestions = Question::factory()
                ->count(4)
                ->easy()
                ->create(['category_id' => $category->id]);

            $mediumQuestions = Question::factory()
                ->count(6)
                ->medium()
                ->create(['category_id' => $category->id]);

            $hardQuestions = Question::factory()
                ->count(3)
                ->hard()
                ->create(['category_id' => $category->id]);

            $withImages = Question::factory()
                ->count(1)
                ->withImage()
                ->create(['category_id' => $category->id]);

            $categoryQuestions = $easyQuestions
                ->merge($mediumQuestions)
                ->merge($hardQuestions)
                ->merge($withImages);

            foreach ($categoryQuestions as $question) {
                for ($i = 1; $i <= 4; $i++) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'text' => fake()->sentence(),
                        'is_correct' => $i === 1,
                        'order' => $i,
                    ]);
                }
            }

            $allQuestions = $allQuestions->merge($categoryQuestions);
        }

        return $allQuestions;
    }

    private function seedQuizzes($questions)
    {
        $quizzes = collect();

        for ($i = 1; $i <= 10; $i++) {
            $questionCount = fake()->randomElement([10, 15, 20, 25, 30]);
            $selectedQuestions = $questions->random($questionCount);

            $quiz = Quiz::create([
                'title' => fake()->sentence(4),
                'description' => fake()->paragraph(),
                'question_count' => $questionCount,
                'time_limit_minutes' => $i <= 6 ? fake()->numberBetween(30, 60) : null,
                'shuffle_questions' => fake()->boolean(),
                'shuffle_answers' => fake()->boolean(),
                'allow_multiple_attempts' => fake()->boolean(60),
            ]);

            $quiz->questions()->attach($selectedQuestions->pluck('id'));

            $quizzes->push($quiz);
        }

        return $quizzes;
    }

    private function seedAttempts($users, $quizzes): void
    {
        $students = $users->students;

        for ($i = 0; $i < 30; $i++) {
            $student = $students->random();
            $quiz = $quizzes->random();
            $quizQuestions = $quiz->questions;

            $startedAt = fake()->dateTimeBetween('-30 days', 'now');
            $submittedAt = fake()->dateTimeBetween($startedAt, 'now');
            $timeTaken = $submittedAt->getTimestamp() - $startedAt->getTimestamp();

            $correctAnswers = fake()->numberBetween(
                (int) ($quiz->question_count * 0.5),
                $quiz->question_count
            );
            $wrongAnswers = $quiz->question_count - $correctAnswers;
            $score = ($correctAnswers / $quiz->question_count) * 100;

            $attempt = Attempt::create([
                'user_id' => $student->id,
                'quiz_id' => $quiz->id,
                'started_at' => $startedAt,
                'submitted_at' => $submittedAt,
                'score' => round($score, 2),
                'correct_count' => $correctAnswers,
                'wrong_count' => $wrongAnswers,
                'time_taken_seconds' => $timeTaken,
            ]);

            foreach ($quizQuestions as $index => $question) {
                $options = $question->options;
                $isCorrect = $index < $correctAnswers;
                $selectedOption = $isCorrect
                    ? $options->where('is_correct', true)->first()
                    : $options->where('is_correct', false)->random();

                if (fake()->boolean(5)) {
                    AttemptAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'selected_option_id' => null,
                        'is_correct' => null,
                        'time_spent' => fake()->numberBetween(5, 30),
                    ]);
                } else {
                    AttemptAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'selected_option_id' => $selectedOption->id,
                        'is_correct' => $isCorrect,
                        'time_spent' => fake()->numberBetween(10, 180),
                    ]);
                }
            }
        }

        for ($i = 0; $i < 10; $i++) {
            $student = $students->random();
            $quiz = $quizzes->random();

            Attempt::create([
                'user_id' => $student->id,
                'quiz_id' => $quiz->id,
                'started_at' => fake()->dateTimeBetween('-7 days', 'now'),
                'submitted_at' => null,
                'score' => null,
                'correct_count' => null,
                'wrong_count' => null,
                'time_taken_seconds' => null,
            ]);
        }
    }
}
