<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
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
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ],
            );

            $user->forceFill([
                'role' => $userData['role'],
            ])->save();
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
                'contacts' => [
                    ['first_name' => 'Nina', 'last_name' => 'Petrova', 'email' => 'nina@acme-logistics.example', 'phone' => '+1 555 0111', 'position' => 'Operations Manager'],
                    ['first_name' => 'Roman', 'last_name' => 'Sokolov', 'email' => 'roman@acme-logistics.example', 'phone' => '+1 555 0112', 'position' => 'Account Lead'],
                ],
            ],
            [
                'name' => 'Moon Rabbit Supply',
                'type' => CompanyType::Vendor,
                'status' => CompanyStatus::Active,
                'email' => 'hello@moon-rabbit.example',
                'phone' => '+1 555 0201',
                'contacts' => [
                    ['first_name' => 'Elena', 'last_name' => 'Volkova', 'email' => 'elena@moon-rabbit.example', 'phone' => '+1 555 0211', 'position' => 'Vendor Manager'],
                ],
            ],
            [
                'name' => 'Blue Banana Imports',
                'type' => CompanyType::Partner,
                'status' => CompanyStatus::Inactive,
                'email' => 'team@blue-banana.example',
                'phone' => '+1 555 0301',
                'contacts' => [
                    ['first_name' => 'Mark', 'last_name' => 'Ivanov', 'email' => 'mark@blue-banana.example', 'phone' => '+1 555 0311', 'position' => 'Partner Contact'],
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
            }

            foreach ($contacts as $contactData) {
                $contact = $company->contacts()->withTrashed()->updateOrCreate(
                    ['email' => $contactData['email']],
                    $contactData,
                );

                if ($contact->trashed()) {
                    $contact->restore();
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
                'due_at' => now()->addDays(2),
                'notes' => ['Nina needs an update before Friday.'],
            ],
            [
                'company' => $acme,
                'contact_id' => null,
                'title' => 'Check contract renewal date',
                'description' => null,
                'status' => TaskStatus::Open,
                'priority' => TaskPriority::Normal,
                'due_at' => now()->addWeek(),
                'notes' => [],
            ],
            [
                'company' => $vendor,
                'contact_id' => $vendorContact->id,
                'title' => 'Review vendor price list',
                'description' => 'Vendor sent new wholesale rates.',
                'status' => TaskStatus::Waiting,
                'priority' => TaskPriority::Normal,
                'due_at' => now()->addDays(5),
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
            }

            foreach ($notes as $body) {
                $note = $task->notes()->withTrashed()->firstOrNew([
                    'body' => $body,
                ]);

                $note->forceFill([
                    'author_id' => $manager->id,
                ])->save();

                if ($note->trashed()) {
                    $note->restore();
                }
            }
        }

        $companyNote = $acme->notes()->withTrashed()->firstOrNew([
            'body' => 'Delivery follow-up is active.',
        ]);

        $companyNote->forceFill([
            'author_id' => $manager->id,
        ])->save();

        if ($companyNote->trashed()) {
            $companyNote->restore();
        }
    }
}
