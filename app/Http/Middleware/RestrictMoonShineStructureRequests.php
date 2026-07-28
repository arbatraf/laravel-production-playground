<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RestrictMoonShineStructureRequests
{
    private const VARY_HEADERS = [
        'Accept',
        'Authorization',
        'Cookie',
        'X-MS-Structure',
        'X-MS-Without-States',
        'X-MS-Only-Layout',
        'X-MS-Without-Layout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->wantsJson() || ! $request->hasHeader('X-MS-Structure')) {
            return $next($request);
        }

        abort_unless(
            $request->routeIs('moonshine.page') && $request->route('pageUri') === 'dashboard',
            Response::HTTP_FORBIDDEN,
        );

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->setVary(self::VARY_HEADERS);

        return $response;
    }
}
