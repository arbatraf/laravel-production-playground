<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Users\DeleteBackofficeUserAction;
use App\Actions\Users\SaveBackofficeUserAction;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class BackofficeUserActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_action_rechecks_a_stale_user_role(): void
    {
        $submittedUser = User::factory()->viewer()->create();

        User::query()
            ->whereKey($submittedUser->getKey())
            ->update(['role' => UserRole::Admin->value]);

        $actor = $submittedUser->fresh();
        $this->assertInstanceOf(User::class, $actor);
        $this->actingAs($actor, 'backoffice');

        $this->assertThrows(
            fn (): User => app(SaveBackofficeUserAction::class)($submittedUser, []),
            ValidationException::class,
            'At least one admin is required.',
        );

        $this->assertSame(UserRole::Admin, $submittedUser->fresh()->role);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_administrator_can_demote_self_when_another_administrator_remains(): void
    {
        $administrator = User::factory()->admin()->create();
        $otherAdministrator = User::factory()->admin()->create();

        $this->actingAs($administrator, 'backoffice');
        $administrator->forceFill(['role' => UserRole::Manager]);

        $user = app(SaveBackofficeUserAction::class)($administrator, []);

        $this->assertSame(UserRole::Manager, $user->role);
        $this->assertSame(UserRole::Admin, $otherAdministrator->fresh()->role);
        $this->assertDatabaseCount('audit_events', 1);
    }

    public function test_actions_enforce_the_user_policy_when_called_directly(): void
    {
        $actor = User::factory()->manager()->create();
        $target = User::factory()->viewer()->create();
        $newUser = new User;
        $newUser->forceFill([
            'name' => 'New user',
            'email' => 'new-user@example.com',
            'role' => UserRole::Viewer,
            'password' => 'new-password',
        ]);

        $this->actingAs($actor, 'backoffice');

        $this->assertThrows(
            fn (): User => app(SaveBackofficeUserAction::class)($newUser, []),
            AuthorizationException::class,
        );

        $target->forceFill(['role' => UserRole::Manager]);

        $this->assertThrows(
            fn (): User => app(SaveBackofficeUserAction::class)($target, []),
            AuthorizationException::class,
        );

        $this->assertThrows(
            fn (): bool => app(DeleteBackofficeUserAction::class)($target),
            AuthorizationException::class,
        );

        $this->assertDatabaseMissing('users', ['email' => 'new-user@example.com']);
        $this->assertSame(UserRole::Viewer, $target->fresh()->role);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_user_update_rolls_back_when_audit_recording_fails(): void
    {
        $actor = User::factory()->admin()->create();
        $submittedUser = User::factory()->viewer()->create();
        $submittedUser->forceFill(['role' => UserRole::Manager]);

        AuditEvent::creating(static function (): never {
            throw new RuntimeException('Audit storage unavailable.');
        });

        $this->actingAs($actor, 'backoffice');

        $this->assertThrows(
            fn (): User => app(SaveBackofficeUserAction::class)($submittedUser, []),
            RuntimeException::class,
            'Audit storage unavailable.',
        );

        $this->assertSame(UserRole::Viewer, $submittedUser->fresh()->role);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_user_delete_rolls_back_when_audit_recording_fails(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->viewer()->create();

        AuditEvent::creating(static function (): never {
            throw new RuntimeException('Audit storage unavailable.');
        });

        $this->actingAs($actor, 'backoffice');

        $this->assertThrows(
            fn (): bool => app(DeleteBackofficeUserAction::class)($target),
            RuntimeException::class,
            'Audit storage unavailable.',
        );

        $this->assertDatabaseHas('users', ['id' => $target->getKey()]);
        $this->assertDatabaseCount('audit_events', 0);
    }
}
