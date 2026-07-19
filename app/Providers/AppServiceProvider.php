<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use App\Policies\AuditEventPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ContactPolicy;
use App\Policies\NotePolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define(
            'accessBackoffice',
            static fn (User $user): bool => in_array($user->role, [
                UserRole::Admin,
                UserRole::Manager,
                UserRole::Viewer,
            ], true),
        );

        Gate::policy(AuditEvent::class, AuditEventPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Contact::class, ContactPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
    }
}
