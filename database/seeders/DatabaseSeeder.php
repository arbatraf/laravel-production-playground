<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
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
}
