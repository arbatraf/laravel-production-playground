<?php

namespace Tests\Feature;

use App\Services\Health\ReadinessChecker;
use Illuminate\Support\Str;
use Tests\TestCase;

class HealthApiTest extends TestCase
{
    public function test_health_endpoint_reports_application_status(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
            ]);
    }

    public function test_health_endpoint_is_rate_limited_by_ip(): void
    {
        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
                ->getJson('/api/v1/health')
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->getJson('/api/v1/health')
            ->assertTooManyRequests();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.11'])
            ->getJson('/api/v1/health')
            ->assertOk();
    }

    public function test_readiness_endpoint_reports_infrastructure_checks(): void
    {
        $this->getJson('/api/v1/health/readiness')
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonCount(5, 'checks')
            ->assertJsonFragment(['name' => 'database', 'status' => 'pass'])
            ->assertJsonFragment(['name' => 'cache', 'status' => 'pass'])
            ->assertJsonFragment(['name' => 'storage', 'status' => 'pass'])
            ->assertJsonFragment(['name' => 'queue', 'status' => 'pass'])
            ->assertJsonFragment(['name' => 'environment', 'status' => 'pass']);
    }

    public function test_readiness_endpoint_returns_unavailable_when_a_check_fails(): void
    {
        $this->instance(ReadinessChecker::class, new class extends ReadinessChecker
        {
            /**
             * @return array{status: 'not_ready', checks: list<array{name: string, status: 'fail'}>}
             */
            public function run(): array
            {
                return [
                    'status' => 'not_ready',
                    'checks' => [
                        ['name' => 'database', 'status' => 'fail'],
                    ],
                ];
            }
        });

        $this->getJson('/api/v1/health/readiness')
            ->assertStatus(503)
            ->assertExactJson([
                'status' => 'not_ready',
                'checks' => [
                    ['name' => 'database', 'status' => 'fail'],
                ],
            ]);
    }

    public function test_readiness_endpoint_requires_configured_bearer_token(): void
    {
        config()->set('health.readiness_token', str_repeat('a', 32));

        $this->getJson('/api/v1/health/readiness')
            ->assertUnauthorized();

        $this->withToken('wrong-token')
            ->getJson('/api/v1/health/readiness')
            ->assertUnauthorized();

        $this->withToken(str_repeat('a', 32))
            ->getJson('/api/v1/health/readiness')
            ->assertOk()
            ->assertJsonPath('status', 'ready');
    }

    public function test_readiness_endpoint_rejects_a_weak_configured_token(): void
    {
        config()->set('health.readiness_token', 'short-token');

        $this->withToken('short-token')
            ->getJson('/api/v1/health/readiness')
            ->assertServiceUnavailable();
    }

    public function test_readiness_endpoint_is_rate_limited(): void
    {
        config()->set('health.readiness_token', str_repeat('a', 32));

        for ($attempt = 1; $attempt <= 12; $attempt++) {
            $this->withToken(str_repeat('a', 32))
                ->getJson('/api/v1/health/readiness')
                ->assertOk();
        }

        $this->withToken(str_repeat('a', 32))
            ->getJson('/api/v1/health/readiness')
            ->assertTooManyRequests();
    }

    public function test_readiness_fails_closed_in_production_without_a_token(): void
    {
        config()->set('health.readiness_token');

        $environment = app()->environment();
        app()->instance('env', 'production');

        try {
            $response = $this->getJson('/api/v1/health/readiness');
        } finally {
            app()->instance('env', $environment);
        }

        $response
            ->assertServiceUnavailable()
            ->assertHeader('Content-Security-Policy', "base-uri 'self'; form-action 'self'; frame-ancestors 'none'")
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertIsString($requestId);
        $this->assertTrue(Str::isUuid($requestId));
    }
}
