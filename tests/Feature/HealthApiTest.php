<?php

namespace Tests\Feature;

use App\Services\Health\ReadinessChecker;
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
}
