<?php

declare(strict_types=1);

use App\Http\Middleware\AuthenticateBackofficeSession;
use App\Http\Middleware\EnsureBackofficeAccess;
use App\Models\User;
use App\MoonShine\Auth\DisableRememberMe;
use App\MoonShine\Forms\LoginForm;
use App\MoonShine\Layouts\MoonShineLayout;
use App\MoonShine\Pages\Dashboard;
use App\MoonShine\Pages\LoginPage;
use App\MoonShine\Palettes\LppPalette;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MoonShine\Crud\Forms\FiltersForm;
use MoonShine\Laravel\Exceptions\MoonShineNotFoundException;
use MoonShine\Laravel\Http\Middleware\Authenticate;
use MoonShine\Laravel\Http\Middleware\ChangeLocale;
use MoonShine\Laravel\Pages\ErrorPage;

return [
    'title' => env('MOONSHINE_TITLE', 'Laravel Production Playground'),
    'logo' => env('MOONSHINE_LOGO', '/brand/logo.svg'),
    'logo_small' => env('MOONSHINE_LOGO_SMALL', '/brand/logo-small.svg'),
    'favicons' => [
        'apple-touch' => '/brand/apple-touch-icon.png',
        '32' => '/brand/favicon-32x32.png',
        '16' => '/brand/favicon-16x16.png',
        'safari-pinned-tab' => '/brand/safari-pinned-tab.svg',
    ],
    'use_migrations' => false,
    'use_notifications' => false,
    'use_database_notifications' => false,
    'use_routes' => true,
    'use_profile' => false,
    'domain' => env('MOONSHINE_DOMAIN'),
    'prefix' => 'backoffice',
    'page_prefix' => 'page',
    'resource_prefix' => 'resource',
    'home_route' => 'moonshine.index',
    'not_found_exception' => MoonShineNotFoundException::class,
    'middleware' => [
        ConvertEmptyStringsToNull::class,
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        VerifyCsrfToken::class,
        SubstituteBindings::class,
        ChangeLocale::class,
    ],
    'disk' => 'public',
    'disk_options' => [],
    'cache' => 'file',
    'auth' => [
        'enabled' => true,
        'guard' => 'backoffice',
        'model' => User::class,
        'middleware' => [
            Authenticate::class,
            AuthenticateBackofficeSession::class,
            EnsureBackofficeAccess::class,
        ],
        'pipelines' => [
            DisableRememberMe::class,
        ],
    ],
    'user_fields' => [
        'username' => 'email',
        'password' => 'password',
        'name' => 'name',
        'avatar' => 'avatar',
    ],
    'layout' => MoonShineLayout::class,
    'palette' => LppPalette::class,
    'forms' => [
        'login' => LoginForm::class,
        'filters' => FiltersForm::class,
    ],
    'pages' => [
        'dashboard' => Dashboard::class,
        'login' => LoginPage::class,
        'error' => ErrorPage::class,
    ],
    'locale' => 'en',
    'locale_key' => ChangeLocale::KEY,
    'locales' => [],
];
