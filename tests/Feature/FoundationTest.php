<?php

namespace Tests\Feature;

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
}
