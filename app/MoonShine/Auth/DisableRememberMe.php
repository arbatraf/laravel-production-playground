<?php

declare(strict_types=1);

namespace App\MoonShine\Auth;

use Closure;
use MoonShine\Laravel\Http\Requests\LoginFormRequest;

final class DisableRememberMe
{
    public function handle(LoginFormRequest $request, Closure $next): mixed
    {
        $request->merge(['remember' => false]);

        return $next($request);
    }
}
