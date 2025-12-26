<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    public function test_admin_can_view_category_list_page(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->assertSuccessful();
    }

    public function test_non_admin_cannot_view_category_list_page(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email' => 'user@example.com',
        ]);

        $this->actingAs($user);

        Livewire::test(ListCategories::class)
            ->assertForbidden();
    }

    public function test_admin_can_see_all_categories_in_table(): void
    {
        $admin = User::factory()->admin()->create();
        $category1 = Category::factory()->create(['name' => 'PHP Basics']);
        $category2 = Category::factory()->create(['name' => 'Laravel Advanced']);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$category1, $category2])
            ->assertCountTableRecords(2);
    }

    public function test_admin_can_create_category_via_modal(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callAction('create', [
                'name' => 'Laravel Basics',
                'slug' => 'laravel-basics',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Category::class, [
            'name' => 'Laravel Basics',
            'slug' => 'laravel-basics',
        ]);
    }

    public function test_admin_can_edit_category_via_modal(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callTableAction('edit', $category, [
                'name' => 'Updated Name',
                'slug' => 'updated-name',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Category::class, [
            'id' => $category->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callTableAction('delete', $category);

        $this->assertDatabaseMissing(Category::class, [
            'id' => $category->id,
        ]);
    }

    public function test_deleting_category_cascades_to_questions(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $question = Question::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callTableAction('delete', $category);

        $this->assertDatabaseMissing(Category::class, [
            'id' => $category->id,
        ]);

        $this->assertDatabaseMissing(Question::class, [
            'id' => $question->id,
        ]);
    }

    public function test_admin_can_search_categories_by_name(): void
    {
        $admin = User::factory()->admin()->create();
        $category1 = Category::factory()->create(['name' => 'Laravel Framework']);
        $category2 = Category::factory()->create(['name' => 'PHP Programming']);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->searchTable('Laravel')
            ->assertCanSeeTableRecords([$category1])
            ->assertCanNotSeeTableRecords([$category2]);
    }

    public function test_admin_can_search_categories_by_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $category1 = Category::factory()->create([
            'name' => 'Laravel',
            'slug' => 'laravel-framework',
        ]);
        $category2 = Category::factory()->create([
            'name' => 'PHP',
            'slug' => 'php-basics',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->searchTable('laravel-framework')
            ->assertCanSeeTableRecords([$category1])
            ->assertCanNotSeeTableRecords([$category2]);
    }

    public function test_table_shows_questions_count_for_category(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        Question::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$category]);

        $this->assertSame(3, $category->fresh()->questions()->count());
    }

    public function test_create_form_requires_name(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callAction('create', [
                'name' => '',
                'slug' => 'test-slug',
            ])
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_form_requires_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callAction('create', [
                'name' => 'Test Category',
                'slug' => '',
            ])
            ->assertHasFormErrors(['slug' => 'required']);
    }

    public function test_slug_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        Category::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callAction('create', [
                'name' => 'New Category',
                'slug' => 'existing-slug',
            ])
            ->assertHasFormErrors(['slug']);
    }

    public function test_name_has_max_length(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callAction('create', [
                'name' => str_repeat('a', 101),
                'slug' => 'test-slug',
            ])
            ->assertHasFormErrors(['name']);
    }

    public function test_slug_has_max_length(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callAction('create', [
                'name' => 'Test Category',
                'slug' => str_repeat('a', 101),
            ])
            ->assertHasFormErrors(['slug']);
    }

    public function test_slug_must_be_alpha_dash(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->callAction('create', [
                'name' => 'Test Category',
                'slug' => 'invalid slug with spaces',
            ])
            ->assertHasFormErrors(['slug']);
    }

    public function test_empty_category_shows_zero_questions(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$category]);

        $this->assertSame(0, $category->fresh()->questions()->count());
    }

    public function test_table_renders_all_expected_columns(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListCategories::class)
            ->assertCanRenderTableColumn('name')
            ->assertCanRenderTableColumn('slug')
            ->assertCanRenderTableColumn('questions_count')
            ->assertCanRenderTableColumn('created_at');
    }
}
