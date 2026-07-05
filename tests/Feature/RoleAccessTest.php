<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_operations_records(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $task = Task::factory()->for($company)->for($contact)->create();
        $note = Note::factory()->for($task, 'notable')->create();

        $this->assertTrue(Gate::forUser($admin)->allows('create', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $company));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $contact));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $task));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $note));
    }

    public function test_manager_can_write_operations_records_but_not_manage_users(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->viewer()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $task = Task::factory()->for($company)->for($contact)->create();
        $note = Note::factory()->for($task, 'notable')->for($manager, 'author')->create();

        $this->assertTrue(Gate::forUser($manager)->allows('create', Company::class));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $company));
        $this->assertTrue(Gate::forUser($manager)->allows('create', Contact::class));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $contact));
        $this->assertTrue(Gate::forUser($manager)->allows('create', Task::class));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $task));
        $this->assertTrue(Gate::forUser($manager)->allows('create', Note::class));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $note));
        $this->assertFalse(Gate::forUser($manager)->allows('update', $user));
        $this->assertFalse(Gate::forUser($manager)->allows('delete', $company));
        $this->assertFalse(Gate::forUser($manager)->allows('delete', $task));
    }

    public function test_viewer_can_read_operations_data_without_write_access(): void
    {
        $viewer = User::factory()->viewer()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $task = Task::factory()->for($company)->for($contact)->create();
        $note = Note::factory()->for($task, 'notable')->create();

        $this->assertTrue(Gate::forUser($viewer)->allows('view', $company));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $contact));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $task));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $note));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', Company::class));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $contact));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', Task::class));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', Note::class));
    }

    public function test_admin_cannot_force_delete_operations_records_with_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for(Company::factory())->create();
        $task = Task::factory()->create();

        Note::factory()->for($company, 'notable')->create();
        Note::factory()->for($contact, 'notable')->create();
        Note::factory()->for($task, 'notable')->create();

        $this->assertFalse(Gate::forUser($admin)->allows('forceDelete', $company));
        $this->assertFalse(Gate::forUser($admin)->allows('forceDelete', $contact));
        $this->assertFalse(Gate::forUser($admin)->allows('forceDelete', $task));
    }

    public function test_guest_cannot_access_operations_policies(): void
    {
        $this->assertFalse(Gate::allows('viewAny', Company::class));
        $this->assertFalse(Gate::allows('create', Contact::class));
        $this->assertFalse(Gate::allows('viewAny', Task::class));
        $this->assertFalse(Gate::allows('create', Note::class));
    }
}
