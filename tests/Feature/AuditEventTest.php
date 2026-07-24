<?php

namespace Tests\Feature;

use App\Actions\Audit\RecordAuditEventAction;
use App\Actions\Audit\RecordBackofficeResourceAuditAction;
use App\Actions\Tasks\ChangeTaskStatusAction;
use App\Enums\AuditEventType;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class AuditEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_audit_event_with_actor_subject_properties_and_request_id(): void
    {
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->create();

        $event = (new RecordAuditEventAction)(
            eventType: 'task.priority_changed',
            description: 'Task priority changed from normal to high.',
            user: $admin,
            subject: $task,
            properties: [
                'from_priority' => 'normal',
                'to_priority' => 'high',
            ],
            requestId: 'req-audit-001',
        );

        $this->assertSame('task.priority_changed', $event->event_type);
        $this->assertSame('Task priority changed from normal to high.', $event->description);
        $this->assertSame('req-audit-001', $event->request_id);
        $this->assertSame(['from_priority' => 'normal', 'to_priority' => 'high'], $event->properties);
        $this->assertTrue($event->user->is($admin));
        $this->assertTrue($event->subject->is($task));
    }

    public function test_records_audit_event_without_actor_or_subject(): void
    {
        $event = (new RecordAuditEventAction)(
            eventType: 'system.audit_marker',
            description: 'System audit marker recorded.',
        );

        $this->assertNull($event->user_id);
        $this->assertNull($event->subject_type);
        $this->assertNull($event->subject_id);
        $this->assertNull($event->properties);
        $this->assertNull($event->request_id);
    }

    public function test_audit_event_keeps_soft_deleted_subject_context(): void
    {
        $company = Company::factory()->create();

        $event = (new RecordAuditEventAction)(
            eventType: 'company.archived',
            description: 'Company archived.',
            subject: $company,
        );

        $company->delete();

        $event = $event->fresh();
        $subject = $event->subject;

        if (! $subject instanceof Company) {
            $this->fail('Audit subject is missing.');
        }

        $this->assertTrue($company->auditEvents()->whereKey($event->id)->exists());
        $this->assertTrue($subject->trashed());
    }

    public function test_audit_event_rejects_sensitive_property_keys(): void
    {
        $keys = [
            'accessToken',
            'authorization',
            'bearer',
            'body',
            'clientSecret',
            'cookie',
            'headers',
            'password_hash',
            'private-key',
            'rawPayload',
            'request',
            'x-api-key',
        ];

        foreach ($keys as $key) {
            try {
                (new RecordAuditEventAction)(
                    eventType: 'task.status_changed',
                    description: 'Task status changed.',
                    properties: [
                        $key => 'hidden',
                    ],
                );

                $this->fail('Sensitive audit property was accepted.');
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Audit property key is sensitive.', $e->getMessage());
            }
        }

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_audit_event_rejects_nested_property_values(): void
    {
        try {
            (new RecordAuditEventAction)(
                eventType: 'task.status_changed',
                description: 'Task status changed.',
                properties: [
                    'changes' => ['from_status' => 'open'],
                ],
            );

            $this->fail('Nested audit property was accepted.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Audit property value must be scalar.', $e->getMessage());
        }

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_audit_event_rejects_empty_property_keys(): void
    {
        try {
            (new RecordAuditEventAction)(
                eventType: 'task.status_changed',
                description: 'Task status changed.',
                properties: [
                    '' => 'open',
                ],
            );

            $this->fail('Empty audit property key was accepted.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Audit property key is invalid.', $e->getMessage());
        }

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_sensitive_audit_columns_are_not_mass_assignable(): void
    {
        $fillable = (new AuditEvent)->getFillable();

        $this->assertNotContains('user_id', $fillable);
        $this->assertNotContains('subject_type', $fillable);
        $this->assertNotContains('subject_id', $fillable);
        $this->assertContains('event_type', $fillable);
        $this->assertContains('description', $fillable);
    }

    public function test_task_status_change_records_audit_event(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->inProgress()->create();

        $task = app(ChangeTaskStatusAction::class)(
            task: $task,
            status: TaskStatus::Done,
            user: $manager,
            requestId: 'req-task-001',
        );

        $event = AuditEvent::query()->sole();

        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertSame('task.status_changed', $event->event_type);
        $this->assertSame('Task status changed from in_progress to done.', $event->description);

        $properties = $event->properties;

        $this->assertIsArray($properties);
        $this->assertSame('in_progress', $properties['from_status']);
        $this->assertSame('done', $properties['to_status']);
        $this->assertSame('req-task-001', $event->request_id);
        $this->assertTrue($event->user->is($manager));
        $this->assertTrue($event->subject->is($task));
    }

    public function test_task_status_noop_does_not_record_audit_event(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->create();

        app(ChangeTaskStatusAction::class)($task, TaskStatus::Open, $manager);

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_unchanged_resource_does_not_record_an_update_audit_event(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create()->refresh();

        app(RecordBackofficeResourceAuditAction::class)
            ->updated($company, $admin, 'req-company-noop');

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_invalid_task_status_transition_does_not_record_audit_event(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->done()->create();

        try {
            app(ChangeTaskStatusAction::class)($task, TaskStatus::InProgress, $manager);
            $this->fail('Invalid transition was accepted.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid task status transition.', $e->getMessage());
        }

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_stale_task_status_cannot_overwrite_a_closed_task(): void
    {
        $manager = User::factory()->manager()->create();
        $submittedTask = Task::factory()->create();
        $completedAt = now();

        Task::query()
            ->whereKey($submittedTask->getKey())
            ->update([
                'status' => TaskStatus::Done->value,
                'completed_at' => $completedAt,
            ]);

        $this->assertThrows(
            fn (): Task => app(ChangeTaskStatusAction::class)($submittedTask, TaskStatus::Waiting, $manager),
            InvalidArgumentException::class,
            'Invalid task status transition.',
        );

        $task = $submittedTask->fresh();

        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_replayed_task_status_change_records_one_audit_event(): void
    {
        $manager = User::factory()->manager()->create();
        $submittedTask = Task::factory()->create();
        $action = app(ChangeTaskStatusAction::class);

        $firstResult = $action($submittedTask, TaskStatus::InProgress, $manager, 'req-task-replay');
        $secondResult = $action($submittedTask, TaskStatus::InProgress, $manager, 'req-task-replay');

        $this->assertNotSame($submittedTask, $firstResult);
        $this->assertNotSame($firstResult, $secondResult);
        $this->assertSame(TaskStatus::InProgress, $secondResult->status);
        $this->assertDatabaseCount('audit_events', 1);
    }

    public function test_task_status_noop_still_requires_update_access(): void
    {
        $viewer = User::factory()->viewer()->create();
        $task = Task::factory()->create();

        $this->assertThrows(
            fn (): Task => app(ChangeTaskStatusAction::class)($task, TaskStatus::Open, $viewer),
            AuthorizationException::class,
        );

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_task_status_change_rolls_back_when_audit_write_fails(): void
    {
        $manager = User::factory()->manager()->create();
        $task = Task::factory()->inProgress()->create();

        AuditEvent::creating(static function (): never {
            throw new RuntimeException('Audit storage unavailable.');
        });

        $this->assertThrows(
            fn (): Task => app(ChangeTaskStatusAction::class)($task, TaskStatus::Done, $manager),
            RuntimeException::class,
            'Audit storage unavailable.',
        );

        $task = $task->fresh();

        $this->assertSame(TaskStatus::InProgress, $task->status);
        $this->assertNull($task->completed_at);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_user_update_audit_omits_password_values_and_hashes(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->viewer()->create();
        $originalPasswordHash = $user->password;

        $user->forceFill([
            'role' => UserRole::Manager,
            'password' => 'new-password',
        ])->save();

        app(RecordBackofficeResourceAuditAction::class)
            ->updated($user, $admin, 'req-user-update');

        $event = AuditEvent::query()->sole();
        $properties = $event->properties;

        $this->assertIsArray($properties);

        $encodedProperties = json_encode($properties, JSON_THROW_ON_ERROR);

        $this->assertSame(AuditEventType::ResourceUpdated->value, $event->event_type);
        $this->assertSame('req-user-update', $event->request_id);
        $this->assertSame('viewer', $properties['from_role']);
        $this->assertSame('manager', $properties['to_role']);
        $this->assertTrue($properties['credentials_changed']);
        $this->assertStringContainsString('password', $properties['changed_fields']);
        $this->assertStringNotContainsString($originalPasswordHash, $encodedProperties);
        $this->assertStringNotContainsString($user->password, $encodedProperties);
        $this->assertStringNotContainsString('new-password', $encodedProperties);
    }
}
