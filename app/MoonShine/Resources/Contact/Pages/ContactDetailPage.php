<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Contact\Pages;

use App\Models\Contact;
use App\MoonShine\Resources\Contact\ContactResource;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends DetailPage<ContactResource>
 */
final class ContactDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Company', 'company_id', static fn (Contact $contact): string => $contact->company->name),
            Text::make('First name', 'first_name'),
            Text::make('Last name', 'last_name'),
            Email::make('Email', 'email'),
            Text::make('Phone', 'phone'),
            Text::make('Position', 'position'),
            Date::make('Created', 'created_at')->format('Y-m-d H:i'),
            Date::make('Updated', 'updated_at')->format('Y-m-d H:i'),
        ];
    }
}
