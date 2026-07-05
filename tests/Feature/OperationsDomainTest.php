<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_assigns_viewer_role_by_default(): void
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

    public function test_company_enum_casts_and_scopes_filter_records(): void
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

    public function test_sensitive_fields_are_not_mass_assignable(): void
    {
        $this->assertNotContains('role', (new User)->getFillable());
        $this->assertNotContains('company_id', (new Contact)->getFillable());
        $this->assertContains('type', (new Company)->getFillable());
    }
}
