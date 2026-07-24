<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DatabaseManager;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connectionName = config('database.default');
$connection = is_string($connectionName)
    ? config("database.connections.{$connectionName}")
    : null;

$isSafeConfiguration = $app->environment('testing')
    && $connectionName === 'mysql'
    && is_array($connection)
    && ($connection['driver'] ?? null) === 'mysql'
    && ($connection['host'] ?? null) === '127.0.0.1'
    && (string) ($connection['port'] ?? '') === '3306'
    && ($connection['database'] ?? null) === 'laravel_production_playground_testing'
    && empty($connection['url'])
    && empty($connection['unix_socket']);

if (! $isSafeConfiguration) {
    fwrite(STDERR, "Laravel is not configured for the local isolated testing database.\n");
    exit(1);
}

try {
    $database = $app->make(DatabaseManager::class)
        ->connection('mysql')
        ->selectOne('SELECT DATABASE() AS database_name');
} catch (Throwable) {
    fwrite(STDERR, "Unable to connect to the local isolated testing database.\n");
    exit(1);
}

if (data_get($database, 'database_name') !== 'laravel_production_playground_testing') {
    fwrite(STDERR, "MySQL did not select the isolated testing database.\n");
    exit(1);
}
