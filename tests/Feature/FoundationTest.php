<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_homepage_confirms_laravel_foundation_is_installed(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Foundation installed')
            ->assertSee('Laravel Production Playground')
            ->assertSee('Laravel 13');
    }

    public function test_application_name_matches_project_name(): void
    {
        $this->assertSame('Laravel Production Playground', config('app.name'));
    }

    public function test_laravel_health_route_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_responses_include_security_headers_and_replace_invalid_request_ids(): void
    {
        $response = $this->withHeader('X-Request-ID', 'untrusted-client-id')->get('/');

        $response
            ->assertOk()
            ->assertHeader('Content-Security-Policy', "base-uri 'self'; form-action 'self'; frame-ancestors 'none'")
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');

        $requestId = $response->headers->get('X-Request-ID');

        $this->assertIsString($requestId);
        $this->assertTrue(Str::isUuid($requestId));
        $this->assertNotSame('untrusted-client-id', $requestId);

        $nextRequestId = $this->get('/')->headers->get('X-Request-ID');

        $this->assertNotSame($requestId, $nextRequestId);
    }

    public function test_valid_upstream_request_id_is_preserved(): void
    {
        $requestId = (string) Str::uuid();

        $this->withHeader('X-Request-ID', $requestId)
            ->get('/')
            ->assertHeader('X-Request-ID', $requestId);
    }

    public function test_hsts_is_only_added_to_secure_production_responses(): void
    {
        $testingResponse = $this->get('https://example.test/');

        $this->assertFalse($testingResponse->headers->has('Strict-Transport-Security'));

        $environment = app()->environment();
        app()->instance('env', 'production');

        try {
            $productionResponse = $this->get('https://example.test/');
            $productionHttpResponse = $this->get('http://example.test/');
        } finally {
            app()->instance('env', $environment);
        }

        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $productionResponse->headers->get('Strict-Transport-Security'),
        );
        $this->assertFalse($productionHttpResponse->headers->has('Strict-Transport-Security'));
    }

    public function test_error_responses_include_security_headers_and_preserve_request_id(): void
    {
        $requestId = (string) Str::uuid();
        $environment = app()->environment();
        app()->instance('env', 'production');

        try {
            $response = $this->withHeader('X-Request-ID', $requestId)
                ->get('https://example.test/missing');
        } finally {
            app()->instance('env', $environment);
        }

        $response
            ->assertNotFound()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertHeader('Content-Security-Policy', "base-uri 'self'; form-action 'self'; frame-ancestors 'none'")
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
