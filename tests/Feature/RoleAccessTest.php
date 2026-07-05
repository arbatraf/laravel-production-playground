<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_users_companies_and_contacts(): void
    {
        $admin = User::factory()->admin()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();

        $this->assertTrue(Gate::forUser($admin)->allows('create', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $company));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $contact));
    }

    public function test_manager_can_write_companies_and_contacts_but_not_manage_users(): void
    {
        $manager = User::factory()->manager()->create();
        $user = User::factory()->viewer()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();

        $this->assertTrue(Gate::forUser($manager)->allows('create', Company::class));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $company));
        $this->assertTrue(Gate::forUser($manager)->allows('create', Contact::class));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $contact));
        $this->assertFalse(Gate::forUser($manager)->allows('update', $user));
        $this->assertFalse(Gate::forUser($manager)->allows('delete', $company));
    }

    public function test_viewer_can_read_operations_data_without_write_access(): void
    {
        $viewer = User::factory()->viewer()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->for($company)->create();

        $this->assertTrue(Gate::forUser($viewer)->allows('view', $company));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $contact));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', Company::class));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $contact));
    }

    public function test_guest_cannot_access_operations_policies(): void
    {
        $this->assertFalse(Gate::allows('viewAny', Company::class));
        $this->assertFalse(Gate::allows('create', Contact::class));
    }
}
