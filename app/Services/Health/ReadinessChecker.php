<?php

namespace App\Services\Health;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ReadinessChecker
{
    public function run(): array
    {
        $checks = [
            $this->check('database', fn (): bool => DB::connection()->getPdo() !== null),
            $this->check('cache', fn (): bool => $this->cacheIsWritable()),
            $this->check('storage', fn (): bool => File::isDirectory(storage_path('framework')) && File::isWritable(storage_path('framework'))),
            $this->check('queue', fn (): bool => $this->queueIsConfigured()),
            $this->check('environment', fn (): bool => $this->environmentIsReady()),
        ];

        return [
            'status' => collect($checks)->every(fn (array $check): bool => $check['status'] === 'pass') ? 'ready' : 'not_ready',
            'checks' => $checks,
        ];
    }

    private function check(string $name, Closure $callback): array
    {
        try {
            $passed = $callback();
        } catch (Throwable) {
            $passed = false;
        }

        return [
            'name' => $name,
            'status' => $passed ? 'pass' : 'fail',
        ];
    }

    private function cacheIsWritable(): bool
    {
        $key = 'lpp:readiness:'.Str::uuid();

        Cache::put($key, 'ok', now()->addMinute());
        $stored = Cache::get($key) === 'ok';
        Cache::forget($key);

        return $stored;
    }

    private function queueIsConfigured(): bool
    {
        $connection = config('queue.default');

        return is_string($connection)
            && $connection !== ''
            && config("queue.connections.{$connection}.driver") !== null;
    }

    private function environmentIsReady(): bool
    {
        return filled(config('app.name'))
            && filled(config('app.env'))
            && filled(config('app.key'));
    }
}
