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
use PHPUnit\Framework\Attributes\DataProvider;
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
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->inProgress()->create();

        $task = app(ChangeTaskStatusAction::class)($task, TaskStatus::Done, $manager);

        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_closed_task_status_cannot_be_reopened(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->done()->create();

        $this->expectException(InvalidArgumentException::class);

        app(ChangeTaskStatusAction::class)($task, TaskStatus::InProgress, $manager);
    }

    #[DataProvider('taskStatusTransitions')]
    public function test_task_status_transition_matrix(TaskStatus $from, TaskStatus $to, bool $allowed): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create([
            'status' => $from,
            'completed_at' => $from->isClosed() ? now() : null,
        ]);

        if (! $allowed) {
            $this->assertThrows(
                fn (): Task => app(ChangeTaskStatusAction::class)($task, $to, $manager),
                InvalidArgumentException::class,
                'Invalid task status transition.',
            );

            $this->assertSame($from, $task->fresh()->status);

            return;
        }

        $task = app(ChangeTaskStatusAction::class)($task, $to, $manager);

        $this->assertSame($to, $task->status);
        $this->assertSame($to->isClosed(), $task->completed_at !== null);
    }

    /**
     * @return iterable<string, array{TaskStatus, TaskStatus, bool}>
     */
    public static function taskStatusTransitions(): iterable
    {
        yield 'open to open' => [TaskStatus::Open, TaskStatus::Open, true];
        yield 'open to in progress' => [TaskStatus::Open, TaskStatus::InProgress, true];
        yield 'open to waiting' => [TaskStatus::Open, TaskStatus::Waiting, true];
        yield 'open to done' => [TaskStatus::Open, TaskStatus::Done, true];
        yield 'open to canceled' => [TaskStatus::Open, TaskStatus::Canceled, true];
        yield 'in progress to open' => [TaskStatus::InProgress, TaskStatus::Open, false];
        yield 'in progress to in progress' => [TaskStatus::InProgress, TaskStatus::InProgress, true];
        yield 'in progress to waiting' => [TaskStatus::InProgress, TaskStatus::Waiting, true];
        yield 'in progress to done' => [TaskStatus::InProgress, TaskStatus::Done, true];
        yield 'in progress to canceled' => [TaskStatus::InProgress, TaskStatus::Canceled, true];
        yield 'waiting to open' => [TaskStatus::Waiting, TaskStatus::Open, true];
        yield 'waiting to in progress' => [TaskStatus::Waiting, TaskStatus::InProgress, true];
        yield 'waiting to waiting' => [TaskStatus::Waiting, TaskStatus::Waiting, true];
        yield 'waiting to done' => [TaskStatus::Waiting, TaskStatus::Done, false];
        yield 'waiting to canceled' => [TaskStatus::Waiting, TaskStatus::Canceled, true];
        yield 'done to open' => [TaskStatus::Done, TaskStatus::Open, false];
        yield 'done to in progress' => [TaskStatus::Done, TaskStatus::InProgress, false];
        yield 'done to waiting' => [TaskStatus::Done, TaskStatus::Waiting, false];
        yield 'done to done' => [TaskStatus::Done, TaskStatus::Done, true];
        yield 'done to canceled' => [TaskStatus::Done, TaskStatus::Canceled, false];
        yield 'canceled to open' => [TaskStatus::Canceled, TaskStatus::Open, false];
        yield 'canceled to in progress' => [TaskStatus::Canceled, TaskStatus::InProgress, false];
        yield 'canceled to waiting' => [TaskStatus::Canceled, TaskStatus::Waiting, false];
        yield 'canceled to done' => [TaskStatus::Canceled, TaskStatus::Done, false];
        yield 'canceled to canceled' => [TaskStatus::Canceled, TaskStatus::Canceled, true];
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
