<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Note\Pages;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Task;
use App\MoonShine\Fields\PlainTextarea;
use App\MoonShine\Resources\Note\NoteResource;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\MorphTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Preview;

/**
 * @extends FormPage<NoteResource>
 */
final class NoteFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        $subject = $this->getResource()->isItemExists()
            ? Preview::make(
                'Subject',
                'notable_id',
                static fn (Note $note): string => e(self::subjectLabel($note)),
            )
            : MorphTo::make('Subject', 'notable')->types([
                Company::class => ['name', 'Company'],
                Contact::class => ['last_name', 'Contact'],
                Task::class => ['title', 'Task'],
            ]);

        return [
            Box::make([
                $subject,
                PlainTextarea::make('Body', 'body')->required(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        $rules = [
            'body' => ['required', 'string', 'max:10000'],
        ];

        if ($item->getKey() !== null) {
            return $rules;
        }

        $notableType = request()->string('notable_type')->toString();
        $notableExists = match ($notableType) {
            Company::class => Rule::exists(Company::class, 'id')->whereNull('deleted_at'),
            Contact::class => Rule::exists(Contact::class, 'id')->whereNull('deleted_at'),
            Task::class => Rule::exists(Task::class, 'id')->whereNull('deleted_at'),
            default => Rule::in([]),
        };

        return [
            'notable_type' => ['required', Rule::in([Company::class, Contact::class, Task::class])],
            'notable_id' => ['required', 'integer', $notableExists],
            ...$rules,
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
