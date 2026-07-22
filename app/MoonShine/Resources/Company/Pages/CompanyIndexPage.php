<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Company\Pages;

use App\Models\Company;
use App\MoonShine\Resources\Company\CompanyResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<CompanyResource>
 */
final class CompanyIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Name', 'name')->sortable(),
            Text::make('Type', 'type', static fn (Company $company): string => $company->type->label()),
            Text::make('Status', 'status', static fn (Company $company): string => $company->status->label()),
            Email::make('Email', 'email'),
            Text::make('Phone', 'phone'),
            Date::make('Created', 'created_at')->format('Y-m-d H:i'),
        ];
    }
}
