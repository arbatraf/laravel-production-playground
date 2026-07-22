<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Note\Pages;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Task;
use App\MoonShine\Resources\Note\NoteResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<NoteResource>
 */
final class NoteIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Subject', 'notable_id', static fn (Note $note): string => self::subjectLabel($note)),
            Text::make('Author', 'author_id', static fn (Note $note): string => (string) data_get($note->author, 'name', '')),
            Text::make('Body', 'body'),
            Date::make('Created', 'created_at')->format('Y-m-d H:i')->sortable(),
        ];
    }

    private static function subjectLabel(Note $note): string
    {
        return match (true) {
            $note->notable instanceof Company => "Company: {$note->notable->name}",
            $note->notable instanceof Contact => 'Contact: '.trim("{$note->notable->first_name} {$note->notable->last_name}"),
            $note->notable instanceof Task => "Task: {$note->notable->title}",
            default => '',
        };
    }
}
