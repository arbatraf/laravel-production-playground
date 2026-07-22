<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureReadinessAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('health.readiness_token');

        if (! is_string($token) || $token === '') {
            abort_unless(app()->environment(['local', 'testing']), Response::HTTP_SERVICE_UNAVAILABLE);

            return $next($request);
        }

        abort_unless(strlen($token) >= 32, Response::HTTP_SERVICE_UNAVAILABLE);

        $providedToken = $request->bearerToken();

        abort_unless(
            is_string($providedToken) && hash_equals($token, $providedToken),
            Response::HTTP_UNAUTHORIZED,
        );

        return $next($request);
    }
}
