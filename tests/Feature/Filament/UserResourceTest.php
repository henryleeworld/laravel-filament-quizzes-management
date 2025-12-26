<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    public function test_admin_can_view_user_list_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertSuccessful();
    }

    public function test_non_admin_cannot_view_user_list_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user);

        Livewire::test(ListUsers::class)
            ->assertForbidden();
    }

    public function test_admin_can_see_all_users_in_table(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $user1 = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
        ]);

        $user2 = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@test.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$admin, $user1, $user2])
            ->assertCountTableRecords(3);
    }

    public function test_admin_can_see_correct_table_columns(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Test Admin']);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanRenderTableColumn('name')
            ->assertCanRenderTableColumn('email')
            ->assertCanRenderTableColumn('is_admin')
            ->assertCanRenderTableColumn('created_at');
    }

    public function test_admin_can_search_users_by_name(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->searchTable('John')
            ->assertCanSeeTableRecords([$user1])
            ->assertCanNotSeeTableRecords([$user2]);
    }

    public function test_admin_can_search_users_by_email(): void
    {
        $admin = User::factory()->admin()->create();
        $user1 = User::factory()->create(['email' => 'john@test.com']);
        $user2 = User::factory()->create(['email' => 'jane@test.com']);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->searchTable('john@test.com')
            ->assertCanSeeTableRecords([$user1])
            ->assertCanNotSeeTableRecords([$user2]);
    }

    public function test_admin_can_access_edit_user_page(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['name' => 'Test User']);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->assertSuccessful();
    }

    public function test_non_admin_cannot_access_edit_user_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $targetUser = User::factory()->create(['name' => 'Test User']);

        $this->actingAs($user);

        Livewire::test(EditUser::class, ['record' => $targetUser->id])
            ->assertForbidden();
    }

    public function test_admin_can_update_user_name(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'user@test.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'New Name',
                'email' => 'user@test.com',
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_admin_can_update_user_email(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'old@test.com',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => 'Test User',
                'email' => 'new@test.com',
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new@test.com',
        ]);
    }

    public function test_admin_can_update_user_is_admin_status(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_admin' => true,
        ]);
    }

    public function test_edit_form_requires_name_field(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => '',
                'email' => $user->email,
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_edit_form_requires_email_field(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => '',
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasFormErrors(['email' => 'required']);
    }

    public function test_edit_form_requires_valid_email_format(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm([
                'name' => $user->name,
                'email' => 'invalid-email',
                'is_admin' => false,
            ])
            ->call('save')
            ->assertHasFormErrors(['email']);
    }

    public function test_create_page_does_not_exist_in_resource(): void
    {
        $pages = UserResource::getPages();

        $this->assertArrayNotHasKey('create', $pages);
    }

    public function test_edit_page_exists_in_resource(): void
    {
        $pages = UserResource::getPages();

        $this->assertArrayHasKey('edit', $pages);
    }

    public function test_index_page_exists_in_resource(): void
    {
        $pages = UserResource::getPages();

        $this->assertArrayHasKey('index', $pages);
    }

    public function test_delete_action_is_not_available_in_table(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertTableActionDoesNotExist('delete');
    }
}
