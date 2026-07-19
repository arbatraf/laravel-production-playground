<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\MenuManager\Attributes\SkipMenu;

#[SkipMenu]
final class Dashboard extends Page
{
    public function getTitle(): string
    {
        return 'Dashboard';
    }

    protected function components(): iterable
    {
        return [];
    }
}
