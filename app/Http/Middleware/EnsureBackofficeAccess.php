<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use MoonShine\Laravel\MoonShineAuth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureBackofficeAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = MoonShineAuth::getGuard()->user();

        abort_unless(
            $user instanceof User && Gate::forUser($user)->allows('accessBackoffice'),
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }
}
