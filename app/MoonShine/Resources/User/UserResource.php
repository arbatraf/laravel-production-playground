<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\User;

use App\Actions\Users\DeleteBackofficeUserAction;
use App\Actions\Users\SaveBackofficeUserAction;
use App\Models\User;
use App\MoonShine\Resources\User\Pages\UserFormPage;
use App\MoonShine\Resources\User\Pages\UserIndexPage;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Crud\Attributes\DestroyHandler;
use MoonShine\Crud\Attributes\SaveHandler;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<User, UserIndexPage, UserFormPage, null>
 */
#[SaveHandler(SaveBackofficeUserAction::class)]
#[DestroyHandler(DeleteBackofficeUserAction::class)]
final class UserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Users';

    protected string $column = 'name';

    protected bool $withPolicy = true;

    public function save(DataWrapperContract $item, ?FieldsContract $fields = null): DataWrapperContract
    {
        $isCreating = $item->getKey() === null;
        $result = parent::save($item, $fields);
        $this->isRecentlyCreated = $isCreating;

        return $result;
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->except(Action::VIEW, Action::MASS_DELETE);
    }

    protected function pages(): array
    {
        return [
            UserIndexPage::class,
            UserFormPage::class,
        ];
    }
}
