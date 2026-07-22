<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Contact\Pages;

use App\Models\Company;
use App\MoonShine\Fields\PlainEmail;
use App\MoonShine\Fields\PlainText;
use App\MoonShine\Resources\Company\CompanyResource;
use App\MoonShine\Resources\Contact\ContactResource;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;

/**
 * @extends FormPage<ContactResource>
 */
final class ContactFormPage extends FormPage
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
                PlainText::make('First name', 'first_name')->required(),
                PlainText::make('Last name', 'last_name')->required(),
                PlainEmail::make('Email', 'email')->nullable(),
                PlainText::make('Phone', 'phone')->nullable(),
                PlainText::make('Position', 'position')->nullable(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists(Company::class, 'id')->whereNull('deleted_at'),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
        ];
    }
}
