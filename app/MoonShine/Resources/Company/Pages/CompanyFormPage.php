<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Company\Pages;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\MoonShine\Fields\PlainEmail;
use App\MoonShine\Fields\PlainText;
use App\MoonShine\Fields\PlainUrl;
use App\MoonShine\Resources\Company\CompanyResource;
use Illuminate\Validation\Rule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Select;

/**
 * @extends FormPage<CompanyResource>
 */
final class CompanyFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                PlainText::make('Name', 'name')->required(),
                Select::make('Type', 'type')->options([
                    CompanyType::Customer->value => CompanyType::Customer->label(),
                    CompanyType::Vendor->value => CompanyType::Vendor->label(),
                    CompanyType::Partner->value => CompanyType::Partner->label(),
                ])->required(),
                Select::make('Status', 'status')->options([
                    CompanyStatus::Active->value => CompanyStatus::Active->label(),
                    CompanyStatus::Inactive->value => CompanyStatus::Inactive->label(),
                ])->required(),
                PlainUrl::make('Website', 'website')->nullable(),
                PlainEmail::make('Email', 'email')->nullable(),
                PlainText::make('Phone', 'phone')->nullable(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CompanyType::class)],
            'status' => ['required', Rule::enum(CompanyStatus::class)],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
        ];
    }
}
