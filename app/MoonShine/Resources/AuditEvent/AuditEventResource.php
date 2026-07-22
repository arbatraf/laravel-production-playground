<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AuditEvent;

use App\Models\AuditEvent;
use App\MoonShine\Resources\AuditEvent\Pages\AuditEventDetailPage;
use App\MoonShine\Resources\AuditEvent\Pages\AuditEventIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * @extends ModelResource<AuditEvent, AuditEventIndexPage, null, AuditEventDetailPage>
 */
final class AuditEventResource extends ModelResource
{
    protected string $model = AuditEvent::class;

    protected string $title = 'Audit events';

    protected string $column = 'description';

    protected array $with = ['subject', 'user'];

    protected bool $withPolicy = true;

    protected function activeActions(): ListOf
    {
        return parent::activeActions()->only(Action::VIEW);
    }

    protected function pages(): array
    {
        return [
            AuditEventIndexPage::class,
            AuditEventDetailPage::class,
        ];
    }
}
