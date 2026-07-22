<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use App\MoonShine\Resources\AuditEvent\AuditEventResource;
use App\MoonShine\Resources\Company\CompanyResource;
use App\MoonShine\Resources\Contact\ContactResource;
use App\MoonShine\Resources\Note\NoteResource;
use App\MoonShine\Resources\Task\TaskResource;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use MoonShine\Crud\Contracts\Fields\HasAsyncSearchContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use Tests\TestCase;

class MoonShineResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resources_are_registered_with_policy_checks(): void
    {
        $resourceClasses = [
            CompanyResource::class,
            ContactResource::class,
            TaskResource::class,
            NoteResource::class,
            UserResource::class,
            AuditEventResource::class,
        ];

        foreach ($resourceClasses as $resourceClass) {
            $this->assertTrue($this->resource($resourceClass)->isWithPolicy());
        }

        $userResource = $this->resource(UserResource::class);
        $this->assertTrue($userResource->hasAction(Action::CREATE));
        $this->assertTrue($userResource->hasAction(Action::UPDATE));
        $this->assertTrue($userResource->hasAction(Action::DELETE));
        $this->assertFalse($userResource->hasAction(Action::VIEW));
        $this->assertFalse($userResource->hasAction(Action::MASS_DELETE));

        $auditResource = $this->resource(AuditEventResource::class);
        $this->assertTrue($auditResource->hasAction(Action::VIEW));
        $this->assertFalse($auditResource->hasAction(Action::CREATE));
        $this->assertFalse($auditResource->hasAction(Action::UPDATE));
        $this->assertFalse($auditResource->hasAction(Action::DELETE));
        $this->assertFalse($auditResource->hasAction(Action::MASS_DELETE));
    }

    public function test_resource_routes_require_the_backoffice_guard(): void
    {
        $resource = $this->resource(CompanyResource::class);

        $this->get($resource->getIndexPageUrl())
            ->assertRedirect(route('moonshine.login'));

        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get($resource->getIndexPageUrl())
            ->assertRedirect(route('moonshine.login'));
    }

    public function test_large_relation_fields_use_bounded_async_search(): void
    {
        $fields = [
            $this->resource(ContactResource::class)->getFormFields()->onlyFields()->findByColumn('company_id'),
            $this->resource(TaskResource::class)->getFormFields()->onlyFields()->findByColumn('company_id'),
            $this->resource(TaskResource::class)->getFormFields()->onlyFields()->findByColumn('contact_id'),
        ];

        foreach ($fields as $field) {
            $this->assertInstanceOf(HasAsyncSearchContract::class, $field);
            $this->assertTrue($field->isAsyncSearch());
            $this->assertSame(15, $field->getAsyncSearchCount());
        }
    }

    public function test_navigation_follows_resource_view_any_policies(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backoffice')
            ->get(route('moonshine.index'))
            ->assertOk()
            ->assertSee('Operations')
            ->assertSee('Companies')
            ->assertSee('Contacts')
            ->assertSee('Tasks')
            ->assertSee('Notes')
            ->assertSee('Administration')
            ->assertSee('Users')
            ->assertSee('Audit events');

        foreach ([UserRole::Manager, UserRole::Viewer] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user, 'backoffice')
                ->get(route('moonshine.index'))
                ->assertOk()
                ->assertSee('Operations')
                ->assertSee('Companies')
                ->assertSee('Contacts')
                ->assertSee('Tasks')
                ->assertSee('Notes')
                ->assertDontSee('Administration')
                ->assertDontSee('Users')
                ->assertDontSee('Audit events');
        }
    }

    public function test_resource_pages_follow_the_role_matrix(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->manager()->create();
        $viewer = User::factory()->viewer()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $task = Task::factory()->for($company)->for($contact)->create();
        $note = Note::factory()->for($task, 'notable')->for($manager, 'author')->create();
        $auditEvent = AuditEvent::factory()->for($admin, 'user')->forSubject($task)->create();

        $operations = [
            [$this->resource(CompanyResource::class), $company],
            [$this->resource(ContactResource::class), $contact],
            [$this->resource(TaskResource::class), $task],
            [$this->resource(NoteResource::class), $note],
        ];

        foreach ($operations as [$resource, $record]) {
            $this->actingAs($manager, 'backoffice')->get($resource->getIndexPageUrl())->assertOk();
            $this->actingAs($manager, 'backoffice')->get($resource->getDetailPageUrl($record->getKey()))->assertOk();
            $this->actingAs($manager, 'backoffice')->get($resource->getFormPageUrl())->assertOk();
            $this->actingAs($manager, 'backoffice')->get($resource->getFormPageUrl($record->getKey()))->assertOk();

            $this->actingAs($viewer, 'backoffice')->get($resource->getIndexPageUrl())->assertOk();
            $this->actingAs($viewer, 'backoffice')->get($resource->getDetailPageUrl($record->getKey()))->assertOk();
            $this->actingAs($viewer, 'backoffice')->get($resource->getFormPageUrl())->assertForbidden();
            $this->actingAs($viewer, 'backoffice')->get($resource->getFormPageUrl($record->getKey()))->assertForbidden();
        }

        $userResource = $this->resource(UserResource::class);
        $auditResource = $this->resource(AuditEventResource::class);

        $this->actingAs($admin, 'backoffice')->get($userResource->getIndexPageUrl())->assertOk();
        $this->actingAs($admin, 'backoffice')->get($userResource->getFormPageUrl($viewer->getKey()))->assertOk();
        $this->actingAs($admin, 'backoffice')->get($auditResource->getIndexPageUrl())->assertOk();
        $this->actingAs($admin, 'backoffice')->get($auditResource->getDetailPageUrl($auditEvent->getKey()))->assertOk();

        foreach ([$manager, $viewer] as $user) {
            $this->actingAs($user, 'backoffice')->get($userResource->getIndexPageUrl())->assertForbidden();
            $this->actingAs($user, 'backoffice')->getJson($userResource->getRoute('crud.show', $user->getKey()))->assertForbidden();
            $this->actingAs($user, 'backoffice')->get($auditResource->getIndexPageUrl())->assertForbidden();
            $this->actingAs($user, 'backoffice')->get($auditResource->getDetailPageUrl($auditEvent->getKey()))->assertForbidden();
        }
    }

    public function test_direct_operation_writes_follow_the_role_matrix(): void
    {
        $manager = User::factory()->manager()->create();
        $viewer = User::factory()->viewer()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $task = Task::factory()->for($company)->for($contact)->create();
        $managerNote = Note::factory()->for($task, 'notable')->for($manager, 'author')->create();
        $otherNote = Note::factory()->for($task, 'notable')->create();

        $operations = [
            [$this->resource(CompanyResource::class), $company],
            [$this->resource(ContactResource::class), $contact],
            [$this->resource(TaskResource::class), $task],
            [$this->resource(NoteResource::class), $managerNote],
        ];

        foreach ($operations as [$resource, $record]) {
            $this->actingAs($viewer, 'backoffice')->postJson($resource->getRoute('crud.store'))->assertForbidden();
            $this->actingAs($viewer, 'backoffice')->putJson($resource->getRoute('crud.update', $record->getKey()))->assertForbidden();
            $this->actingAs($viewer, 'backoffice')->deleteJson($resource->getRoute('crud.destroy', $record->getKey()))->assertForbidden();
            $this->actingAs($viewer, 'backoffice')->deleteJson($resource->getRoute('crud.massDelete'), [
                'ids' => [$record->getKey()],
            ])->assertForbidden();

            $this->actingAs($manager, 'backoffice')->deleteJson($resource->getRoute('crud.destroy', $record->getKey()))->assertForbidden();
            $this->actingAs($manager, 'backoffice')->deleteJson($resource->getRoute('crud.massDelete'), [
                'ids' => [$record->getKey()],
            ])->assertForbidden();

            $this->assertDatabaseHas($record->getTable(), ['id' => $record->getKey()]);
        }

        $this->actingAs($manager, 'backoffice')
            ->putJson($this->resource(NoteResource::class)->getRoute('crud.update', $otherNote->getKey()), [
                'body' => 'Blocked update',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('notes', [
            'id' => $otherNote->getKey(),
            'body' => 'Blocked update',
        ]);
    }

    public function test_company_form_preserves_plain_text_and_escapes_output(): void
    {
        $manager = User::factory()->manager()->create();
        $resource = $this->resource(CompanyResource::class);
        $name = 'R&D <script>alert("company")</script>';
        $website = 'https://example.test/?first=1&second=2';
        $email = 'sales&support@example.com';
        $phone = 'Main & mobile';

        $this->actingAs($manager, 'backoffice')
            ->post($resource->getRoute('crud.store'), [
                'name' => $name,
                'type' => CompanyType::Customer->value,
                'status' => CompanyStatus::Active->value,
                'website' => $website,
                'email' => $email,
                'phone' => $phone,
            ])
            ->assertRedirect();

        $company = Company::query()->where('email', $email)->firstOrFail();

        $this->assertSame($name, $company->name);
        $this->assertSame($website, $company->website);
        $this->assertSame($email, $company->email);
        $this->assertSame($phone, $company->phone);

        $updatedName = 'R&D <script>alert("updated")</script>';

        $this->actingAs($manager, 'backoffice')
            ->put($resource->getRoute('crud.update', $company->getKey()), [
                'name' => $updatedName,
                'type' => CompanyType::Partner->value,
                'status' => CompanyStatus::Inactive->value,
                'website' => $website,
                'email' => $email,
                'phone' => $phone,
            ])
            ->assertRedirect();

        $this->assertSame($updatedName, $company->refresh()->name);

        $this->actingAs($manager, 'backoffice')
            ->get($resource->getDetailPageUrl($company->getKey()))
            ->assertOk()
            ->assertDontSee('<script>alert("updated")</script>', false)
            ->assertSee('&lt;script&gt;alert(&quot;updated&quot;)&lt;/script&gt;', false);
    }

    public function test_contact_form_preserves_plain_text_on_create_and_update(): void
    {
        $manager = User::factory()->manager()->create();
        $company = Company::factory()->create();
        $resource = $this->resource(ContactResource::class);
        $email = 'r&d@example.com';

        $this->actingAs($manager, 'backoffice')
            ->post($resource->getRoute('crud.store'), [
                'company_id' => $company->getKey(),
                'first_name' => 'R&D <Lead>',
                'last_name' => "O'Connor & Sons",
                'email' => $email,
                'phone' => 'Office & mobile',
                'position' => 'Sales <Lead>',
            ])
            ->assertRedirect();

        $contact = Contact::query()->where('email', $email)->firstOrFail();

        $this->assertSame('R&D <Lead>', $contact->first_name);
        $this->assertSame("O'Connor & Sons", $contact->last_name);
        $this->assertSame($email, $contact->email);
        $this->assertSame('Office & mobile', $contact->phone);
        $this->assertSame('Sales <Lead>', $contact->position);

        $this->actingAs($manager, 'backoffice')
            ->put($resource->getRoute('crud.update', $contact->getKey()), [
                'company_id' => $company->getKey(),
                'first_name' => 'R&D <Director>',
                'last_name' => "O'Connor & Partners",
                'email' => $email,
                'phone' => 'Office & direct',
                'position' => 'Sales <Director>',
            ])
            ->assertRedirect();

        $contact->refresh();

        $this->assertSame('R&D <Director>', $contact->first_name);
        $this->assertSame("O'Connor & Partners", $contact->last_name);
        $this->assertSame('Office & direct', $contact->phone);
        $this->assertSame('Sales <Director>', $contact->position);
    }

    public function test_task_forms_keep_system_fields_server_controlled(): void
    {
        $manager = User::factory()->manager()->create();
        $forgedCreator = User::factory()->admin()->create();
        $assignee = User::factory()->manager()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();
        $resource = $this->resource(TaskResource::class);
        $description = 'Confirm R&D <script>alert("task")</script> scope.';

        $this->actingAs($manager, 'backoffice')
            ->post($resource->getRoute('crud.store'), [
                'company_id' => $company->getKey(),
                'contact_id' => $contact->getKey(),
                'title' => 'Prepare proposal',
                'description' => $description,
                'priority' => TaskPriority::High->value,
                'due_at' => now()->addDay()->format('Y-m-d H:i'),
                'status' => TaskStatus::Done->value,
                'completed_at' => now()->format('Y-m-d H:i'),
                'created_by_user_id' => $forgedCreator->getKey(),
                'assigned_to_user_id' => $assignee->getKey(),
            ])
            ->assertRedirect();

        $task = Task::query()->where('title', 'Prepare proposal')->firstOrFail();

        $this->assertTrue($task->createdBy->is($manager));
        $this->assertNull($task->assignedTo);
        $this->assertSame($description, $task->description);
        $this->assertSame(TaskStatus::Open, $task->status);
        $this->assertNull($task->completed_at);

        $this->actingAs($manager, 'backoffice')
            ->get($resource->getFormPageUrl($task->getKey()))
            ->assertOk()
            ->assertDontSee('<script>alert("task")</script>', false)
            ->assertSee('&lt;script&gt;alert(&quot;task&quot;)&lt;/script&gt;', false);

        $originalCreatorId = $task->created_by_user_id;
        $updatedDescription = 'Confirm revised R&D <script>alert("updated task")</script> scope.';

        $this->actingAs($manager, 'backoffice')
            ->put($resource->getRoute('crud.update', $task->getKey()), [
                'company_id' => $company->getKey(),
                'contact_id' => $contact->getKey(),
                'title' => 'Prepare revised proposal',
                'description' => $updatedDescription,
                'priority' => TaskPriority::Normal->value,
                'due_at' => null,
                'status' => TaskStatus::Done->value,
                'completed_at' => now()->format('Y-m-d H:i'),
                'created_by_user_id' => $forgedCreator->getKey(),
                'assigned_to_user_id' => $assignee->getKey(),
            ])
            ->assertRedirect();

        $task->refresh();

        $this->assertSame('Prepare revised proposal', $task->title);
        $this->assertSame($updatedDescription, $task->description);
        $this->assertSame($originalCreatorId, $task->created_by_user_id);
        $this->assertNull($task->assigned_to_user_id);
        $this->assertSame(TaskStatus::Open, $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_task_forms_reject_mismatched_or_archived_relations(): void
    {
        $manager = User::factory()->manager()->create();
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $contact = Contact::factory()->for($otherCompany)->create();
        $resource = $this->resource(TaskResource::class);

        $payload = [
            'company_id' => $company->getKey(),
            'contact_id' => $contact->getKey(),
            'title' => 'Invalid relation task',
            'priority' => TaskPriority::Normal->value,
        ];

        $this->actingAs($manager, 'backoffice')
            ->postJson($resource->getRoute('crud.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contact_id');

        $company->delete();

        $this->actingAs($manager, 'backoffice')
            ->postJson($resource->getRoute('crud.store'), [
                ...$payload,
                'contact_id' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('company_id');

        $this->assertDatabaseMissing('tasks', ['title' => 'Invalid relation task']);
    }

    public function test_note_forms_keep_author_and_subject_server_controlled(): void
    {
        $manager = User::factory()->manager()->create();
        $forgedAuthor = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $resource = $this->resource(NoteResource::class);
        $body = 'Initial R&D <script>alert("note")</script>';

        $this->actingAs($manager, 'backoffice')
            ->post($resource->getRoute('crud.store'), [
                'notable_type' => Company::class,
                'notable_id' => $company->getKey(),
                'author_id' => $forgedAuthor->getKey(),
                'body' => $body,
            ])
            ->assertRedirect();

        $note = Note::query()->where('body', $body)->firstOrFail();

        $this->assertSame($body, $note->body);
        $this->assertTrue($note->author->is($manager));
        $this->assertTrue($note->notable->is($company));

        $this->actingAs($manager, 'backoffice')
            ->get($resource->getFormPageUrl($note->getKey()))
            ->assertOk()
            ->assertDontSee('<script>alert("note")</script>', false)
            ->assertSee('&lt;script&gt;alert(&quot;note&quot;)&lt;/script&gt;', false);

        $updatedBody = 'Updated R&D <script>alert("updated note")</script>';

        $this->actingAs($manager, 'backoffice')
            ->put($resource->getRoute('crud.update', $note->getKey()), [
                'notable_type' => Company::class,
                'notable_id' => $otherCompany->getKey(),
                'author_id' => $forgedAuthor->getKey(),
                'body' => $updatedBody,
            ])
            ->assertRedirect();

        $note->refresh();

        $this->assertSame($updatedBody, $note->body);
        $this->assertTrue($note->author->is($manager));
        $this->assertTrue($note->notable->is($company));

        $this->actingAs($manager, 'backoffice')
            ->postJson($resource->getRoute('crud.store'), [
                'notable_type' => User::class,
                'notable_id' => $forgedAuthor->getKey(),
                'body' => 'Invalid note',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['notable_type', 'notable_id']);

        $otherCompany->delete();

        $this->actingAs($manager, 'backoffice')
            ->postJson($resource->getRoute('crud.store'), [
                'notable_type' => Company::class,
                'notable_id' => $otherCompany->getKey(),
                'body' => 'Archived subject note',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('notable_id');
    }

    public function test_user_resource_protects_password_and_delete_boundaries(): void
    {
        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->viewer()->create();
        $resource = $this->resource(UserResource::class);
        $oldPassword = $otherUser->password;

        $this->actingAs($admin, 'backoffice')
            ->get($resource->getFormPageUrl($otherUser->getKey()))
            ->assertOk()
            ->assertDontSee($oldPassword);

        $this->actingAs($admin, 'backoffice')
            ->put($resource->getRoute('crud.update', $otherUser->getKey()), [
                'name' => $otherUser->name,
                'email' => $otherUser->email,
                'role' => UserRole::Viewer->value,
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect();

        $this->assertSame($oldPassword, $otherUser->refresh()->password);

        $this->actingAs($admin, 'backoffice')
            ->put($resource->getRoute('crud.update', $otherUser->getKey()), [
                'name' => $otherUser->name,
                'email' => $otherUser->email,
                'role' => UserRole::Manager->value,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $otherUser->refresh()->password));

        $this->actingAs($admin, 'backoffice')
            ->deleteJson($resource->getRoute('crud.destroy', $admin->getKey()))
            ->assertForbidden();

        $this->actingAs($admin, 'backoffice')
            ->deleteJson($resource->getRoute('crud.massDelete'), [
                'ids' => [$otherUser->getKey()],
            ])
            ->assertForbidden();

        $this->actingAs($admin, 'backoffice')
            ->deleteJson($resource->getRoute('crud.destroy', $otherUser->getKey()))
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $otherUser->getKey()]);
    }

    public function test_audit_resource_is_read_only_and_escapes_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->create();
        $auditEvent = AuditEvent::factory()->for($admin, 'user')->forSubject($task)->create([
            'description' => '<script>alert(1)</script>',
            'properties' => ['value' => '<script>alert(2)</script>'],
        ]);
        $resource = $this->resource(AuditEventResource::class);

        $this->actingAs($admin, 'backoffice')
            ->get($resource->getDetailPageUrl($auditEvent->getKey()))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;script&gt;alert(2)&lt;/script&gt;', false);

        $this->actingAs($admin, 'backoffice')->postJson($resource->getRoute('crud.store'))->assertForbidden();
        $this->actingAs($admin, 'backoffice')->putJson($resource->getRoute('crud.update', $auditEvent->getKey()))->assertForbidden();
        $this->actingAs($admin, 'backoffice')->deleteJson($resource->getRoute('crud.destroy', $auditEvent->getKey()))->assertForbidden();
        $this->actingAs($admin, 'backoffice')->deleteJson($resource->getRoute('crud.massDelete'), [
            'ids' => [$auditEvent->getKey()],
        ])->assertForbidden();

        $this->assertDatabaseHas('audit_events', ['id' => $auditEvent->getKey()]);
    }

    /**
     * @template TResource of ModelResource
     *
     * @param  class-string<TResource>  $resourceClass
     * @return TResource
     */
    private function resource(string $resourceClass): ModelResource
    {
        $resource = moonshine()->getResources()->findByClass($resourceClass);

        $this->assertInstanceOf($resourceClass, $resource);

        return $resource;
    }
}
