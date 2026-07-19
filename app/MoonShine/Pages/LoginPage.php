<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Contracts\UI\LayoutContract;
use MoonShine\Core\Attributes\Layout;
use MoonShine\Laravel\Layouts\LoginLayout;
use MoonShine\MenuManager\Attributes\SkipMenu;

#[SkipMenu]
#[Layout(LoginLayout::class)]
final class LoginPage extends \MoonShine\Laravel\Pages\LoginPage
{
    protected function modifyLayout(LayoutContract $layout): LayoutContract
    {
        if ($layout instanceof LoginLayout) {
            $layout
                ->title('Backoffice')
                ->description('Laravel Production Playground');
        }

        return $layout;
    }
}
