<?php

namespace Tests\Feature;

use App\Actions\Audit\RecordAuditEventAction;
use App\Actions\Tasks\ChangeTaskStatusAction;
use App\Enums\TaskStatus;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
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
        try {
            (new RecordAuditEventAction)(
                eventType: 'task.status_changed',
                description: 'Task status changed.',
                properties: [
                    'token' => 'hidden',
                ],
            );

            $this->fail('Sensitive audit property was accepted.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Audit property key is sensitive.', $e->getMessage());
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

        $task = (new ChangeTaskStatusAction)(
            task: $task,
            status: TaskStatus::Done,
            user: $manager,
            requestId: 'req-task-001',
        );

        $event = AuditEvent::query()->sole();

        $this->assertSame(TaskStatus::Done, $task->status);
        $this->assertSame('task.status_changed', $event->event_type);
        $this->assertSame('Task status changed from in_progress to done.', $event->description);
        $this->assertSame(['from_status' => 'in_progress', 'to_status' => 'done'], $event->properties);
        $this->assertSame('req-task-001', $event->request_id);
        $this->assertTrue($event->user->is($manager));
        $this->assertTrue($event->subject->is($task));
    }

    public function test_task_status_noop_does_not_record_audit_event(): void
    {
        $task = Task::factory()->create();

        (new ChangeTaskStatusAction)($task, TaskStatus::Open);

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_invalid_task_status_transition_does_not_record_audit_event(): void
    {
        $task = Task::factory()->done()->create();

        try {
            (new ChangeTaskStatusAction)($task, TaskStatus::InProgress);
            $this->fail('Invalid transition was accepted.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid task status transition.', $e->getMessage());
        }

        $this->assertDatabaseCount('audit_events', 0);
    }
}
