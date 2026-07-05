<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    private const DEMO_PASSWORD_HASH = '$2y$12$rJfU4zp31W7iF7jyZy4X1eyXprArkTboN8oRI4G2AhKJrDcq6QJ3K';

    private const SEEDED_AT = '2026-07-05 09:00:00';

    public function run(): void
    {
        $this->seedDemoUsers();
        $this->seedDemoCompanies();
        $this->seedDemoTasks();
    }

    private function seedDemoUsers(): void
    {
        foreach ([
            ['name' => 'Admin', 'email' => 'admin@example.com', 'role' => UserRole::Admin],
            ['name' => 'Manager', 'email' => 'manager@example.com', 'role' => UserRole::Manager],
            ['name' => 'Viewer', 'email' => 'viewer@example.com', 'role' => UserRole::Viewer],
        ] as $userData) {
            DB::table('users')->updateOrInsert(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'email_verified_at' => $this->at(self::SEEDED_AT),
                    'password' => self::DEMO_PASSWORD_HASH,
                    'role' => $userData['role']->value,
                    ...$this->timestamps(),
                ],
            );
        }
    }

    private function seedDemoCompanies(): void
    {
        $companies = [
            [
                'name' => 'Acme Logistics',
                'type' => CompanyType::Customer,
                'status' => CompanyStatus::Active,
                'email' => 'ops@acme-logistics.example',
                'phone' => '+1 555 0101',
                ...$this->timestamps(),
                'contacts' => [
                    ['first_name' => 'Nina', 'last_name' => 'Petrova', 'email' => 'nina@acme-logistics.example', 'phone' => '+1 555 0111', 'position' => 'Operations Manager', ...$this->timestamps()],
                    ['first_name' => 'Roman', 'last_name' => 'Sokolov', 'email' => 'roman@acme-logistics.example', 'phone' => '+1 555 0112', 'position' => 'Account Lead', ...$this->timestamps()],
                ],
            ],
            [
                'name' => 'Moon Rabbit Supply',
                'type' => CompanyType::Vendor,
                'status' => CompanyStatus::Active,
                'email' => 'hello@moon-rabbit.example',
                'phone' => '+1 555 0201',
                ...$this->timestamps(),
                'contacts' => [
                    ['first_name' => 'Elena', 'last_name' => 'Volkova', 'email' => 'elena@moon-rabbit.example', 'phone' => '+1 555 0211', 'position' => 'Vendor Manager', ...$this->timestamps()],
                ],
            ],
            [
                'name' => 'Blue Banana Imports',
                'type' => CompanyType::Partner,
                'status' => CompanyStatus::Inactive,
                'email' => 'team@blue-banana.example',
                'phone' => '+1 555 0301',
                ...$this->timestamps(),
                'contacts' => [
                    ['first_name' => 'Mark', 'last_name' => 'Ivanov', 'email' => 'mark@blue-banana.example', 'phone' => '+1 555 0311', 'position' => 'Partner Contact', ...$this->timestamps()],
                ],
            ],
        ];

        foreach ($companies as $companyData) {
            $contacts = $companyData['contacts'];
            unset($companyData['contacts']);

            $company = Company::withTrashed()->updateOrCreate(
                ['name' => $companyData['name']],
                $companyData,
            );

            if ($company->trashed()) {
                $company->restore();
                $company->forceFill($this->timestamps())->save();
            }

            foreach ($contacts as $contactData) {
                $contact = $company->contacts()->withTrashed()->updateOrCreate(
                    ['email' => $contactData['email']],
                    $contactData,
                );

                if ($contact->trashed()) {
                    $contact->restore();
                    $contact->forceFill($this->timestamps())->save();
                }
            }
        }
    }

    private function seedDemoTasks(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $acme = Company::query()->where('name', 'Acme Logistics')->firstOrFail();
        $vendor = Company::query()->where('name', 'Moon Rabbit Supply')->firstOrFail();
        $customerContact = $acme->contacts()->where('email', 'nina@acme-logistics.example')->firstOrFail();
        $vendorContact = $vendor->contacts()->where('email', 'elena@moon-rabbit.example')->firstOrFail();

        foreach ([
            [
                'company' => $acme,
                'contact_id' => $customerContact->id,
                'title' => 'Confirm warehouse delivery window',
                'description' => 'Customer asked for an updated delivery slot.',
                'status' => TaskStatus::InProgress,
                'priority' => TaskPriority::High,
                'due_at' => $this->at('2026-07-08 10:00:00'),
                'notes' => ['Nina needs an update before Friday.'],
            ],
            [
                'company' => $acme,
                'contact_id' => null,
                'title' => 'Check contract renewal date',
                'description' => null,
                'status' => TaskStatus::Open,
                'priority' => TaskPriority::Normal,
                'due_at' => $this->at('2026-07-15 14:00:00'),
                'notes' => [],
            ],
            [
                'company' => $vendor,
                'contact_id' => $vendorContact->id,
                'title' => 'Review vendor price list',
                'description' => 'Vendor sent new wholesale rates.',
                'status' => TaskStatus::Waiting,
                'priority' => TaskPriority::Normal,
                'due_at' => $this->at('2026-07-11 11:00:00'),
                'notes' => ['Elena still needs to send the spreadsheet.'],
            ],
        ] as $taskData) {
            $notes = $taskData['notes'];
            $company = $taskData['company'];
            unset($taskData['notes'], $taskData['company']);

            $task = $company->tasks()->withTrashed()->firstOrNew([
                'title' => $taskData['title'],
            ]);

            $task->fill([
                'title' => $taskData['title'],
                'description' => $taskData['description'],
                'priority' => $taskData['priority'],
                'due_at' => $taskData['due_at'],
                ...$this->timestamps(),
            ]);

            $task->forceFill([
                'contact_id' => $taskData['contact_id'],
                'assigned_to_user_id' => $manager->id,
                'created_by_user_id' => $admin->id,
                'status' => $taskData['status'],
                'completed_at' => null,
            ])->save();

            if ($task->trashed()) {
                $task->restore();
                $task->forceFill($this->timestamps())->save();
            }

            foreach ($notes as $body) {
                $note = $task->notes()->withTrashed()->firstOrNew([
                    'body' => $body,
                ]);

                $note->forceFill([
                    'author_id' => $manager->id,
                    ...$this->timestamps(),
                ])->save();

                if ($note->trashed()) {
                    $note->restore();
                    $note->forceFill($this->timestamps())->save();
                }
            }
        }

        $companyNote = $acme->notes()->withTrashed()->firstOrNew([
            'body' => 'Delivery follow-up is active.',
        ]);

        $companyNote->forceFill([
            'author_id' => $manager->id,
            ...$this->timestamps(),
        ])->save();

        if ($companyNote->trashed()) {
            $companyNote->restore();
            $companyNote->forceFill($this->timestamps())->save();
        }
    }

    private function at(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date);
    }

    /**
     * @return array{created_at: CarbonImmutable, updated_at: CarbonImmutable}
     */
    private function timestamps(): array
    {
        $seededAt = $this->at(self::SEEDED_AT);

        return [
            'created_at' => $seededAt,
            'updated_at' => $seededAt,
        ];
    }
}
