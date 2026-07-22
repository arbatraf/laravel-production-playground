<?php

namespace App\Listeners;

use App\Actions\Audit\RecordAuditEventAction;
use App\Enums\AuditEventType;
use App\Http\Middleware\AssignRequestId;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

final readonly class BackofficeAuthenticationListener
{
    public function __construct(
        private RecordAuditEventAction $recordAuditEvent,
    ) {}

    public function handleLogin(Login $event): void
    {
        if ($event->guard !== 'backoffice' || ! $event->user instanceof User) {
            return;
        }

        ($this->recordAuditEvent)(
            eventType: AuditEventType::BackofficeLogin,
            description: 'Backoffice user logged in.',
            user: $event->user,
            subject: $event->user,
            properties: ['guard' => $event->guard],
            requestId: $this->requestId(request()),
        );
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->guard !== 'backoffice' || ! $event->user instanceof User) {
            return;
        }

        ($this->recordAuditEvent)(
            eventType: AuditEventType::BackofficeLogout,
            description: 'Backoffice user logged out.',
            user: $event->user,
            subject: $event->user,
            properties: ['guard' => $event->guard],
            requestId: $this->requestId(request()),
        );
    }

    public function handleLockout(Lockout $event): void
    {
        if (! $event->request->routeIs('moonshine.authenticate')) {
            return;
        }

        ($this->recordAuditEvent)(
            eventType: AuditEventType::BackofficeLockedOut,
            description: 'Backoffice login rate limit reached.',
            properties: ['guard' => 'backoffice'],
            requestId: $this->requestId($event->request),
        );
    }

    private function requestId(Request $request): ?string
    {
        return AssignRequestId::current($request);
    }
}
