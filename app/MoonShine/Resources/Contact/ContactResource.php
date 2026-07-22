<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Contact;

use App\Models\Contact;
use App\MoonShine\Resources\Concerns\AuditsResourceChanges;
use App\MoonShine\Resources\Concerns\LimitsMassDeletion;
use App\MoonShine\Resources\Contact\Pages\ContactDetailPage;
use App\MoonShine\Resources\Contact\Pages\ContactFormPage;
use App\MoonShine\Resources\Contact\Pages\ContactIndexPage;
use MoonShine\Laravel\Resources\ModelResource;

/**
 * @extends ModelResource<Contact, ContactIndexPage, ContactFormPage, ContactDetailPage>
 */
final class ContactResource extends ModelResource
{
    use AuditsResourceChanges, LimitsMassDeletion;

    protected string $model = Contact::class;

    protected string $title = 'Contacts';

    protected string $column = 'last_name';

    protected array $with = ['company'];

    protected bool $withPolicy = true;

    protected function pages(): array
    {
        return [
            ContactIndexPage::class,
            ContactFormPage::class,
            ContactDetailPage::class,
        ];
    }
}
