<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Palettes\LppPalette;
use MoonShine\Contracts\MenuManager\MenuElementContract;
use MoonShine\Laravel\Components\Layout\Profile;
use MoonShine\Laravel\Layouts\AppLayout;

final class MoonShineLayout extends AppLayout
{
    protected ?string $palette = LppPalette::class;

    /**
     * @return list<MenuElementContract>
     */
    protected function menu(): array
    {
        return [];
    }

    protected function getProfileComponent(): Profile
    {
        return Profile::make(
            route: '',
            avatar: static fn (): string => '/brand/logo-small.svg',
        )->menu([]);
    }

    protected function isProfileEnabled(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function getFooterMenu(): array
    {
        return [];
    }

    protected function getFooterCopyright(): string
    {
        return sprintf('&copy; %d Laravel Production Playground', now()->year);
    }
}
