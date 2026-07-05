<?php

namespace Tests\Feature;

use App\Actions\Tasks\ChangeTaskStatusAction;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OperationsDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_start_as_viewers(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Viewer, $user->role);
    }

    public function test_company_groups_contacts_and_keeps_archived_company_history(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();

        $this->assertTrue($company->contacts()->whereKey($contact->id)->exists());

        $company->delete();

        $archivedContact = Contact::query()->findOrFail($contact->id);

        $this->assertSoftDeleted($company);
        $this->assertTrue($archivedContact->company->trashed());
    }

    public function test_company_type_and_status_filter_records(): void
    {
        Company::factory()->create([
            'type' => CompanyType::Customer,
            'status' => CompanyStatus::Active,
        ]);

        Company::factory()->vendor()->inactive()->create();

        $company = Company::query()->active()->type(CompanyType::Customer)->firstOrFail();

        $this->assertSame(CompanyType::Customer, $company->type);
        $this->assertSame(CompanyStatus::Active, $company->status);
        $this->assertSame(1, Company::query()->status(CompanyStatus::Inactive)->count());
    }

    public function test_tasks_keep_company_contact_user_and_note_context(): void
    {
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $manager = User::factory()->manager()->create();
        $admin = User::factory()->admin()->create();
        $task = Task::factory()
            ->for($company)
            ->for($contact)
            ->for($manager, 'assignedTo')
            ->for($admin, 'createdBy')
            ->create();

        $note = Note::factory()
            ->for($task, 'notable')
            ->for($manager, 'author')
            ->create();

        $this->assertTrue($company->tasks()->whereKey($task->id)->exists());
        $this->assertTrue($contact->tasks()->whereKey($task->id)->exists());
        $this->assertTrue($manager->assignedTasks()->whereKey($task->id)->exists());
        $this->assertTrue($task->notes()->whereKey($note->id)->exists());

        $contact->delete();
        $task->delete();

        $archivedTask = Task::withTrashed()->findOrFail($task->id);
        $archivedNote = Note::query()->findOrFail($note->id);

        $this->assertTrue($archivedTask->contact->trashed());
        $this->assertInstanceOf(Task::class, $archivedNote->notable);
        $this->assertTrue($archivedNote->notable->trashed());
    }

    public function test_task_status_transition_closes_task(): void
    {
        $task = Task::factory()->inProgress()->create();

        $task = (new ChangeTaskStatusAction)($task, TaskStatus::Done);

        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_closed_task_status_cannot_be_reopened(): void
    {
        $task = Task::factory()->done()->create();

        $this->expectException(InvalidArgumentException::class);

        (new ChangeTaskStatusAction)($task, TaskStatus::InProgress);
    }

    public function test_task_status_priority_and_assignment_filter_records(): void
    {
        $manager = User::factory()->manager()->create();

        Task::factory()
            ->for($manager, 'assignedTo')
            ->inProgress()
            ->highPriority()
            ->create();

        Task::factory()->done()->create([
            'priority' => TaskPriority::Low,
        ]);

        $task = Task::query()->assignedTo($manager)->open()->status(TaskStatus::InProgress)->firstOrFail();

        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertSame(TaskPriority::High, $task->priority);
        $this->assertSame(1, Task::query()->open()->count());
    }

    public function test_contact_requires_existing_company(): void
    {
        $this->expectException(QueryException::class);

        Contact::factory()->create([
            'company_id' => 999_999,
        ]);
    }

    public function test_company_hard_delete_is_restricted_while_contacts_exist(): void
    {
        $company = Company::factory()->create();

        Contact::factory()->for($company)->create();

        $this->expectException(QueryException::class);

        $company->forceDelete();
    }

    public function test_company_hard_delete_is_restricted_while_tasks_exist(): void
    {
        $company = Company::factory()->create();

        Task::factory()->for($company)->create();

        $this->expectException(QueryException::class);

        $company->forceDelete();
    }

    public function test_sensitive_fields_are_not_mass_assignable(): void
    {
        $this->assertNotContains('role', (new User)->getFillable());
        $this->assertNotContains('company_id', (new Contact)->getFillable());
        $this->assertNotContains('company_id', (new Task)->getFillable());
        $this->assertNotContains('status', (new Task)->getFillable());
        $this->assertNotContains('notable_type', (new Note)->getFillable());
        $this->assertContains('type', (new Company)->getFillable());
        $this->assertContains('title', (new Task)->getFillable());
    }
}
