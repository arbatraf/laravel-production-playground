<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Company\Pages;

use App\Models\Company;
use App\MoonShine\Resources\Company\CompanyResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Url;

/**
 * @extends DetailPage<CompanyResource>
 */
final class CompanyDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Name', 'name'),
            Text::make('Type', 'type', static fn (Company $company): string => $company->type->label()),
            Text::make('Status', 'status', static fn (Company $company): string => $company->status->label()),
            Url::make('Website', 'website'),
            Email::make('Email', 'email'),
            Text::make('Phone', 'phone'),
            Date::make('Created', 'created_at')->format('Y-m-d H:i'),
            Date::make('Updated', 'updated_at')->format('Y-m-d H:i'),
        ];
    }
}
