<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;
use App\MoonShine\Resources\AuditEvent\AuditEventResource;
use App\MoonShine\Resources\Task\TaskResource;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use MoonShine\Laravel\Resources\ModelResource;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MoonShineStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_structure_requires_the_backoffice_guard(): void
    {
        $url = $this->dashboardUrl();

        $this->structureRequest($url)->assertUnauthorized();

        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->structureRequest($url)
            ->assertUnauthorized();
    }

    public function test_backoffice_roles_can_read_the_dashboard_structure(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user, 'backoffice')
                ->structureRequest($this->dashboardUrl());

            $response
                ->assertOk()
                ->assertHeader('Content-Type', 'application/json')
                ->assertJsonPath('type', 'Dashboard')
                ->assertJsonStructure([
                    'type',
                    'components',
                    'states',
                ]);
        }
    }

    public function test_dashboard_structure_supports_the_moonshine_variants(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'backoffice');

        $withoutStates = $this->structureRequest($this->dashboardUrl(), [
            'X-MS-Without-States' => 'true',
        ])->assertOk()->json();

        $this->assertIsArray($withoutStates);
        $this->assertStructureHasNoStates($withoutStates);

        $onlyLayout = $this->structureRequest($this->dashboardUrl(), [
            'X-MS-Only-Layout' => 'true',
        ]);

        $onlyLayout
            ->assertOk()
            ->assertJsonPath('type', 'Layout');

        $layoutJson = $onlyLayout->getContent();

        $this->assertStringContainsString('Operations', $layoutJson);
        $this->assertStringContainsString('Administration', $layoutJson);

        $this->structureRequest($this->dashboardUrl(), [
            'X-MS-Without-Layout' => 'true',
        ])
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_dashboard_layout_follows_navigation_policies(): void
    {
        foreach ([UserRole::Manager, UserRole::Viewer] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $response = $this->actingAs($user, 'backoffice')
                ->structureRequest($this->dashboardUrl(), [
                    'X-MS-Only-Layout' => 'true',
                ])
                ->assertOk();

            $layoutJson = $response->getContent();

            $this->assertStringContainsString('Operations', $layoutJson);
            $this->assertStringNotContainsString('Administration', $layoutJson);
            $this->assertStringNotContainsString('Audit events', $layoutJson);
        }
    }

    public function test_resource_page_structures_are_forbidden_for_every_backoffice_role(): void
    {
        $target = User::factory()->viewer()->create();
        $auditEvent = AuditEvent::factory()->for($target, 'user')->create();
        $urls = [
            $this->resource(TaskResource::class)->getIndexPageUrl(),
            $this->resource(UserResource::class)->getIndexPageUrl(),
            $this->resource(UserResource::class)->getFormPageUrl($target->getKey()),
            $this->resource(AuditEventResource::class)->getIndexPageUrl(),
            $this->resource(AuditEventResource::class)->getDetailPageUrl($auditEvent->getKey()),
        ];

        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            foreach ($urls as $url) {
                $this->actingAs($user, 'backoffice')
                    ->structureRequest($url)
                    ->assertForbidden();
            }
        }
    }

    public function test_structure_header_without_json_accept_keeps_the_html_response(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backoffice')
            ->withHeader('X-MS-Structure', 'true')
            ->get($this->dashboardUrl())
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('Dashboard');
    }

    public function test_dashboard_structure_is_not_shared_or_cached(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'backoffice')
            ->structureRequest($this->dashboardUrl())
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cacheControl = $response->headers->get('Cache-Control');
        $vary = implode(', ', $response->headers->all('Vary'));
        $requestId = $response->headers->get('X-Request-ID');

        $this->assertIsString($cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);

        foreach ([
            'Accept',
            'Authorization',
            'Cookie',
            'X-MS-Structure',
            'X-MS-Without-States',
            'X-MS-Only-Layout',
            'X-MS-Without-Layout',
        ] as $header) {
            $this->assertStringContainsString($header, $vary);
        }

        $this->assertIsString($requestId);
        $this->assertTrue(Str::isUuid($requestId));
        $this->assertStringNotContainsString($admin->password, $response->getContent());
        $this->assertStringNotContainsString((string) $admin->remember_token, $response->getContent());
    }

    private function dashboardUrl(): string
    {
        return route('moonshine.page', ['pageUri' => 'dashboard']);
    }

    /**
     * @param  array<string, string>  $headers
     * @return TestResponse<Response>
     */
    private function structureRequest(string $url, array $headers = []): TestResponse
    {
        return $this->get($url, [
            'Accept' => 'application/json',
            'X-MS-Structure' => 'true',
            ...$headers,
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $structure
     */
    private function assertStructureHasNoStates(array $structure): void
    {
        $this->assertArrayNotHasKey('states', $structure);

        foreach ($structure as $value) {
            if (is_array($value)) {
                $this->assertStructureHasNoStates($value);
            }
        }
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
