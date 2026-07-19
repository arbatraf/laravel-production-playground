<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

final class AuthenticateBackofficeSession extends AuthenticateSession
{
    protected function redirectTo(Request $request): string
    {
        return route('moonshine.login');
    }
}
