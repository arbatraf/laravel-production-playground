<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\AuditEvent\Pages;

use App\Models\AuditEvent;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Task;
use App\Models\User;
use App\MoonShine\Resources\AuditEvent\AuditEventResource;
use Illuminate\Database\Eloquent\Model;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/**
 * @extends IndexPage<AuditEventResource>
 */
final class AuditEventIndexPage extends IndexPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Event', 'event_type'),
            Text::make('Subject', 'subject_id', static fn (AuditEvent $event): string => self::subjectLabel($event)),
            Text::make('User', 'user_id', static fn (AuditEvent $event): string => (string) data_get($event->user, 'name', '')),
            Text::make('Description', 'description'),
            Text::make('Request ID', 'request_id'),
            Date::make('Created', 'created_at')->format('Y-m-d H:i')->sortable(),
        ];
    }

    private static function subjectLabel(AuditEvent $event): string
    {
        return match (true) {
            $event->subject instanceof Company => "Company: {$event->subject->name}",
            $event->subject instanceof Contact => 'Contact: '.trim("{$event->subject->first_name} {$event->subject->last_name}"),
            $event->subject instanceof Task => "Task: {$event->subject->title}",
            $event->subject instanceof User => "User: {$event->subject->name}",
            $event->subject instanceof Model => class_basename($event->subject).' #'.$event->subject->getKey(),
            default => '',
        };
    }
}
