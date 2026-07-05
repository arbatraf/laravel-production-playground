<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperationsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_demo_users_and_operations_records(): void
    {
        $this->seed();
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $viewer = User::query()->where('email', 'viewer@example.com')->firstOrFail();
        $customer = Company::query()->where('name', 'Acme Logistics')->firstOrFail();
        $inactivePartner = Company::query()->where('name', 'Blue Banana Imports')->firstOrFail();
        $deliveryTask = Task::query()->where('title', 'Confirm warehouse delivery window')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame(UserRole::Manager, $manager->role);
        $this->assertSame(UserRole::Viewer, $viewer->role);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertSame(CompanyType::Customer, $customer->type);
        $this->assertSame(CompanyStatus::Inactive, $inactivePartner->status);
        $this->assertSame(TaskStatus::InProgress, $deliveryTask->status);
        $this->assertSame(TaskPriority::High, $deliveryTask->priority);
        $this->assertSame(2, $customer->contacts()->count());
        $this->assertSame(2, $customer->tasks()->count());
        $this->assertSame(1, $deliveryTask->notes()->count());
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('companies', 3);
        $this->assertDatabaseCount('contacts', 4);
        $this->assertDatabaseCount('tasks', 3);
        $this->assertDatabaseCount('notes', 3);
    }

    public function test_database_seeder_restores_demo_operations_records(): void
    {
        $this->seed();

        $company = Company::query()->where('name', 'Acme Logistics')->firstOrFail();
        $contact = $company->contacts()->where('email', 'nina@acme-logistics.example')->firstOrFail();
        $task = $company->tasks()->where('title', 'Confirm warehouse delivery window')->firstOrFail();
        $note = $task->notes()->where('body', 'Nina needs an update before Friday.')->firstOrFail();

        $note->delete();
        $task->delete();
        $contact->delete();
        $company->delete();

        $this->seed();

        $restoredCompany = Company::query()->where('name', 'Acme Logistics')->firstOrFail();
        $restoredContact = $restoredCompany->contacts()->where('email', 'nina@acme-logistics.example')->firstOrFail();
        $restoredTask = $restoredCompany->tasks()->where('title', 'Confirm warehouse delivery window')->firstOrFail();
        $restoredNote = $restoredTask->notes()->where('body', 'Nina needs an update before Friday.')->firstOrFail();

        $this->assertSame($company->id, $restoredCompany->id);
        $this->assertSame($contact->id, $restoredContact->id);
        $this->assertSame($task->id, $restoredTask->id);
        $this->assertSame($note->id, $restoredNote->id);
        $this->assertFalse($restoredCompany->trashed());
        $this->assertFalse($restoredContact->trashed());
        $this->assertFalse($restoredTask->trashed());
        $this->assertFalse($restoredNote->trashed());
        $this->assertSame(3, Company::withTrashed()->count());
        $this->assertSame(4, Contact::withTrashed()->count());
        $this->assertSame(3, Task::withTrashed()->count());
        $this->assertSame(3, Note::withTrashed()->count());
    }
}
