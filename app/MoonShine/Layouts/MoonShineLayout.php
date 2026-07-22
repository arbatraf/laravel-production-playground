<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Palettes\LppPalette;
use App\MoonShine\Resources\AuditEvent\AuditEventResource;
use App\MoonShine\Resources\Company\CompanyResource;
use App\MoonShine\Resources\Contact\ContactResource;
use App\MoonShine\Resources\Note\NoteResource;
use App\MoonShine\Resources\Task\TaskResource;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Contracts\MenuManager\MenuElementContract;
use MoonShine\Laravel\Components\Layout\Profile;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use MoonShine\Support\Enums\Ability;
use MoonShine\UI\Components\Layout\Logo;

final class MoonShineLayout extends AppLayout
{
    protected ?string $palette = LppPalette::class;

    /**
     * @return list<MenuElementContract>
     */
    protected function menu(): array
    {
        return [
            MenuGroup::make('Operations', [
                $this->resourceItem(CompanyResource::class),
                $this->resourceItem(ContactResource::class),
                $this->resourceItem(TaskResource::class),
                $this->resourceItem(NoteResource::class),
            ]),
            MenuGroup::make('Administration', [
                $this->resourceItem(UserResource::class),
                $this->resourceItem(AuditEventResource::class),
            ]),
        ];
    }

    private function resourceItem(string $resource): MenuItem
    {
        return MenuItem::make($resource)->canSee(static function (MenuItem $item): bool {
            $filler = $item->getFiller();

            return $filler instanceof ModelResource
                && $filler->can(Ability::VIEW_ANY);
        });
    }

    protected function getLogoComponent(): Logo
    {
        $logo = $this->getLogo();
        $logoSmall = $this->getLogo(small: true);

        return Logo::make(
            $this->getHomeUrl(),
            "$logo#light",
            "$logoSmall#light",
        )->darkMode(
            "$logo#dark",
            "$logoSmall#dark",
        );
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
