<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Task\Pages;

use App\Enums\TaskPriority;
use App\Models\Company;
use App\Models\Contact;
use App\MoonShine\Fields\PlainText;
use App\MoonShine\Fields\PlainTextarea;
use App\MoonShine\Resources\Company\CompanyResource;
use App\MoonShine\Resources\Contact\ContactResource;
use App\MoonShine\Resources\Task\TaskResource;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Select;

/**
 * @extends FormPage<TaskResource>
 */
final class TaskFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                BelongsTo::make(
                    'Company',
                    'company',
                    formatted: static fn (Company $company): string => $company->name,
                    resource: CompanyResource::class,
                )->asyncSearch('name')
                    ->required(),
                BelongsTo::make(
                    'Contact',
                    'contact',
                    formatted: static fn (Contact $contact): string => trim("{$contact->first_name} {$contact->last_name}"),
                    resource: ContactResource::class,
                )->asyncSearch('last_name')
                    ->nullable(),
                PlainText::make('Title', 'title')->required(),
                PlainTextarea::make('Description', 'description')->nullable(),
                Select::make('Priority', 'priority')->options([
                    TaskPriority::Low->value => TaskPriority::Low->label(),
                    TaskPriority::Normal->value => TaskPriority::Normal->label(),
                    TaskPriority::High->value => TaskPriority::High->label(),
                ])->required(),
                Date::make('Due', 'due_at')->withTime()->nullable(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        $companyId = request()->integer('company_id');

        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists(Company::class, 'id')->whereNull('deleted_at'),
            ],
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists(Contact::class, 'id')->where(
                    static fn (QueryBuilder $query): QueryBuilder => $query
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at'),
                ),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
