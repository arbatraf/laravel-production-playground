<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
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

    public function test_admin_access_matches_policy_matrix(): void
    {
        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->viewer()->create();
        $company = Company::factory()->create();
        $deletableCompany = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $task = Task::factory()->for($company)->for($contact)->create();
        $deletableTask = Task::factory()->create();
        $note = Note::factory()->for($task, 'notable')->for($admin, 'author')->create();
        $auditEvent = AuditEvent::factory()->for($admin, 'user')->forSubject($task)->create();

        $this->assertGateMatrix($admin, 'admin', [
            ['viewAny', AuditEvent::class, true],
            ['view', $auditEvent, true],
            ['create', AuditEvent::class, false],
            ['update', $auditEvent, false],
            ['delete', $auditEvent, false],
            ['massDelete', AuditEvent::class, false],
            ['restore', $auditEvent, false],
            ['forceDelete', $auditEvent, false],

            ['viewAny', User::class, true],
            ['view', $admin, true],
            ['view', $otherUser, true],
            ['create', User::class, true],
            ['update', $admin, true],
            ['update', $otherUser, true],
            ['delete', $admin, false],
            ['delete', $otherUser, true],
            ['massDelete', User::class, false],
            ['restore', $otherUser, false],
            ['forceDelete', $otherUser, false],

            ['viewAny', Company::class, true],
            ['view', $company, true],
            ['create', Company::class, true],
            ['update', $company, true],
            ['delete', $company, true],
            ['massDelete', Company::class, true],
            ['restore', $company, true],
            ['forceDelete', $deletableCompany, true],

            ['viewAny', Contact::class, true],
            ['view', $contact, true],
            ['create', Contact::class, true],
            ['update', $contact, true],
            ['delete', $contact, true],
            ['massDelete', Contact::class, true],
            ['restore', $contact, true],
            ['forceDelete', $contact, true],

            ['viewAny', Task::class, true],
            ['view', $task, true],
            ['create', Task::class, true],
            ['update', $task, true],
            ['delete', $task, true],
            ['massDelete', Task::class, true],
            ['restore', $task, true],
            ['forceDelete', $deletableTask, true],

            ['viewAny', Note::class, true],
            ['view', $note, true],
            ['create', Note::class, true],
            ['update', $note, true],
            ['delete', $note, true],
            ['massDelete', Note::class, true],
            ['restore', $note, true],
            ['forceDelete', $note, true],
        ]);
    }

    public function test_manager_access_matches_policy_matrix(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->viewer()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $task = Task::factory()->for($company)->for($contact)->create();
        $ownNote = Note::factory()->for($task, 'notable')->for($manager, 'author')->create();
        $otherNote = Note::factory()->for($task, 'notable')->create();
        $auditEvent = AuditEvent::factory()->for($manager, 'user')->forSubject($task)->create();

        $this->assertGateMatrix($manager, 'manager', [
            ['viewAny', AuditEvent::class, false],
            ['view', $auditEvent, false],
            ['create', AuditEvent::class, false],
            ['update', $auditEvent, false],
            ['delete', $auditEvent, false],
            ['massDelete', AuditEvent::class, false],
            ['restore', $auditEvent, false],
            ['forceDelete', $auditEvent, false],

            ['viewAny', User::class, false],
            ['view', $manager, true],
            ['view', $user, false],
            ['create', User::class, false],
            ['update', $manager, false],
            ['update', $user, false],
            ['delete', $manager, false],
            ['delete', $user, false],
            ['massDelete', User::class, false],
            ['restore', $user, false],
            ['forceDelete', $user, false],

            ['viewAny', Company::class, true],
            ['view', $company, true],
            ['create', Company::class, true],
            ['update', $company, true],
            ['delete', $company, false],
            ['massDelete', Company::class, false],
            ['restore', $company, false],
            ['forceDelete', $company, false],

            ['viewAny', Contact::class, true],
            ['view', $contact, true],
            ['create', Contact::class, true],
            ['update', $contact, true],
            ['delete', $contact, false],
            ['massDelete', Contact::class, false],
            ['restore', $contact, false],
            ['forceDelete', $contact, false],

            ['viewAny', Task::class, true],
            ['view', $task, true],
            ['create', Task::class, true],
            ['update', $task, true],
            ['delete', $task, false],
            ['massDelete', Task::class, false],
            ['restore', $task, false],
            ['forceDelete', $task, false],

            ['viewAny', Note::class, true],
            ['view', $ownNote, true],
            ['create', Note::class, true],
            ['update', $ownNote, true],
            ['update', $otherNote, false],
            ['delete', $ownNote, false],
            ['massDelete', Note::class, false],
            ['restore', $ownNote, false],
            ['forceDelete', $ownNote, false],
        ]);
    }

    public function test_viewer_access_matches_policy_matrix(): void
    {
        $viewer = User::factory()->viewer()->create();
        $user = User::factory()->manager()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $task = Task::factory()->for($company)->for($contact)->create();
        $note = Note::factory()->for($task, 'notable')->for($viewer, 'author')->create();
        $auditEvent = AuditEvent::factory()->for($viewer, 'user')->forSubject($task)->create();

        $this->assertGateMatrix($viewer, 'viewer', [
            ['viewAny', AuditEvent::class, false],
            ['view', $auditEvent, false],
            ['create', AuditEvent::class, false],
            ['update', $auditEvent, false],
            ['delete', $auditEvent, false],
            ['massDelete', AuditEvent::class, false],
            ['restore', $auditEvent, false],
            ['forceDelete', $auditEvent, false],

            ['viewAny', User::class, false],
            ['view', $viewer, true],
            ['view', $user, false],
            ['create', User::class, false],
            ['update', $viewer, false],
            ['update', $user, false],
            ['delete', $viewer, false],
            ['delete', $user, false],
            ['massDelete', User::class, false],
            ['restore', $user, false],
            ['forceDelete', $user, false],

            ['viewAny', Company::class, true],
            ['view', $company, true],
            ['create', Company::class, false],
            ['update', $company, false],
            ['delete', $company, false],
            ['massDelete', Company::class, false],
            ['restore', $company, false],
            ['forceDelete', $company, false],

            ['viewAny', Contact::class, true],
            ['view', $contact, true],
            ['create', Contact::class, false],
            ['update', $contact, false],
            ['delete', $contact, false],
            ['massDelete', Contact::class, false],
            ['restore', $contact, false],
            ['forceDelete', $contact, false],

            ['viewAny', Task::class, true],
            ['view', $task, true],
            ['create', Task::class, false],
            ['update', $task, false],
            ['delete', $task, false],
            ['massDelete', Task::class, false],
            ['restore', $task, false],
            ['forceDelete', $task, false],

            ['viewAny', Note::class, true],
            ['view', $note, true],
            ['create', Note::class, false],
            ['update', $note, false],
            ['delete', $note, false],
            ['massDelete', Note::class, false],
            ['restore', $note, false],
            ['forceDelete', $note, false],
        ]);
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
        $this->assertFalse(Gate::allows('viewAny', AuditEvent::class));
        $this->assertFalse(Gate::allows('create', AuditEvent::class));
        $this->assertFalse(Gate::allows('viewAny', Company::class));
        $this->assertFalse(Gate::allows('create', Contact::class));
        $this->assertFalse(Gate::allows('viewAny', Task::class));
        $this->assertFalse(Gate::allows('create', Note::class));
    }

    /**
     * @param  list<array{0: string, 1: class-string|object, 2: bool}>  $checks
     */
    private function assertGateMatrix(User $user, string $role, array $checks): void
    {
        foreach ($checks as [$ability, $subject, $expected]) {
            $this->assertSame(
                $expected,
                Gate::forUser($user)->allows($ability, $subject),
                sprintf('%s %s %s', $role, $ability, is_string($subject) ? $subject : $subject::class),
            );
        }
    }
}
