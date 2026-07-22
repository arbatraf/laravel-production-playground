<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Company;

use App\Models\Company;
use App\MoonShine\Resources\Company\Pages\CompanyDetailPage;
use App\MoonShine\Resources\Company\Pages\CompanyFormPage;
use App\MoonShine\Resources\Company\Pages\CompanyIndexPage;
use App\MoonShine\Resources\Concerns\AuditsResourceChanges;
use App\MoonShine\Resources\Concerns\LimitsMassDeletion;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Company, CompanyIndexPage, CompanyFormPage, CompanyDetailPage>
 */
final class CompanyResource extends ModelResource
{
    use AuditsResourceChanges, LimitsMassDeletion;

    protected string $model = Company::class;

    protected string $title = 'Companies';

    protected string $column = 'name';

    protected bool $withPolicy = true;

    protected function pages(): array
    {
        return [
            CompanyIndexPage::class,
            CompanyFormPage::class,
            CompanyDetailPage::class,
        ];
    }
}
