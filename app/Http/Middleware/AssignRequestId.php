<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    private const ATTRIBUTE = 'request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $providedRequestId = $request->headers->get('X-Request-ID');
        $requestId = is_string($providedRequestId) && Str::isUuid($providedRequestId)
            ? $providedRequestId
            : (string) Str::uuid();
        $request->attributes->set(self::ATTRIBUTE, $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    public static function current(Request $request): ?string
    {
        $requestId = $request->attributes->get(self::ATTRIBUTE);

        return is_string($requestId) && $requestId !== '' ? $requestId : null;
    }
}
