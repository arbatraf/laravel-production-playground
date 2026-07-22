<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Note;

use App\Models\Note;
use App\Models\User;
use App\MoonShine\Resources\Note\Pages\NoteDetailPage;
use App\MoonShine\Resources\Note\Pages\NoteFormPage;
use App\MoonShine\Resources\Note\Pages\NoteIndexPage;
use Illuminate\Auth\Access\AuthorizationException;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Note, NoteIndexPage, NoteFormPage, NoteDetailPage>
 */
final class NoteResource extends ModelResource
{
    protected string $model = Note::class;

    protected string $title = 'Notes';

    protected string $column = 'body';

    protected array $with = ['notable', 'author'];

    protected bool $withPolicy = true;

    protected function pages(): array
    {
        return [
            NoteIndexPage::class,
            NoteFormPage::class,
            NoteDetailPage::class,
        ];
    }

    /**
     * @param  DataWrapperContract<Note>  $item
     * @return DataWrapperContract<Note>
     */
    protected function beforeCreating(DataWrapperContract $item): DataWrapperContract
    {
        $user = MoonShineAuth::getGuard()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        $item->getOriginal()->author()->associate($user);

        return $item;
    }
}
