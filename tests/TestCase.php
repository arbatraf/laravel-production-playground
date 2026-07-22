<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\ConfigurationUrlParser;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $configuration = $app['config']->get('database.connections.mysql');

        if (! is_array($configuration)) {
            throw new LogicException('Tests require a MySQL connection configuration.');
        }

        $configuration = (new ConfigurationUrlParser)->parseConfiguration($configuration);

        if ($connection !== 'mysql'
            || ($configuration['driver'] ?? null) !== 'mysql'
            || ($configuration['database'] ?? null) !== 'laravel_production_playground_testing'
        ) {
            throw new LogicException('Tests require the isolated MySQL testing database.');
        }

        return $app;
    }
}
