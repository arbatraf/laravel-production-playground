<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class BackofficeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_backoffice_login(): void
    {
        $this->get(route('moonshine.index'))
            ->assertRedirect(route('moonshine.login'));
    }

    public function test_login_page_uses_project_branding(): void
    {
        $this->get(route('moonshine.login'))
            ->assertOk()
            ->assertSee('Backoffice')
            ->assertSee('Laravel Production Playground')
            ->assertSee('Email')
            ->assertSee('/brand/logo.svg', false)
            ->assertDontSee('name="remember"', false);
    }

    public function test_backoffice_uses_existing_user_accounts(): void
    {
        $user = User::factory()->admin()->create();

        $this->post(route('moonshine.authenticate'), [
            'username' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('moonshine.index'));

        $this->assertAuthenticatedAs($user, 'backoffice');
    }

    public function test_invalid_password_does_not_open_backoffice(): void
    {
        $user = User::factory()->admin()->create();

        $this->post(route('moonshine.authenticate'), [
            'username' => $user->email,
            'password' => 'invalid-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest('backoffice');
    }

    public function test_backoffice_does_not_issue_remember_cookie(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->post(route('moonshine.authenticate'), [
            'username' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('moonshine.index'));

        $hasRememberCookie = collect($response->headers->getCookies())
            ->contains(static fn (Cookie $cookie): bool => str_starts_with($cookie->getName(), 'remember_'));

        $this->assertFalse($hasRememberCookie);
    }

    public function test_operations_roles_can_open_backoffice(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user, 'backoffice')
                ->get(route('moonshine.index'))
                ->assertOk()
                ->assertSee('Laravel Production Playground');

            Auth::guard('backoffice')->logout();
        }
    }

    public function test_backoffice_logo_follows_the_selected_theme(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user, 'backoffice')
            ->get(route('moonshine.index'))
            ->assertOk()
            ->assertSee('/brand/logo.svg#light', false)
            ->assertSee('/brand/logo.svg#dark', false)
            ->assertSee('/brand/logo-small.svg#light', false)
            ->assertSee('/brand/logo-small.svg#dark', false);
    }

    public function test_web_session_does_not_open_backoffice(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('moonshine.index'))
            ->assertRedirect(route('moonshine.login'));
    }

    public function test_password_change_closes_backoffice_session(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user, 'backoffice')
            ->get(route('moonshine.index'))
            ->assertOk();

        $user->forceFill(['password' => Hash::make('changed-password')])->save();

        $this->get(route('moonshine.index'))
            ->assertRedirect(route('moonshine.login'));

        $this->assertGuest('backoffice');
    }

    public function test_logout_closes_backoffice_session(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user, 'backoffice')
            ->delete(route('moonshine.logout'))
            ->assertRedirect(route('moonshine.login'));

        $this->assertGuest('backoffice');
    }
}
