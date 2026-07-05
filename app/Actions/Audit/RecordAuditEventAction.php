<?php

namespace App\Actions\Audit;

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final readonly class RecordAuditEventAction
{
    private const array SENSITIVE_PROPERTY_KEYS = [
        'access_token',
        'api_key',
        'apikey',
        'authorization',
        'bearer',
        'body',
        'client_secret',
        'cookie',
        'headers',
        'password',
        'payload',
        'private_key',
        'raw_payload',
        'refresh_token',
        'request',
        'secret',
        'token',
    ];

    /**
     * @param  array<array-key, mixed>|null  $properties
     */
    public function __invoke(
        AuditEventType|string $eventType,
        string $description,
        ?User $user = null,
        ?Model $subject = null,
        ?array $properties = null,
        ?string $requestId = null,
    ): AuditEvent {
        $eventType = $eventType instanceof AuditEventType
            ? $eventType->value
            : trim($eventType);

        $description = trim($description);

        if ($eventType === '') {
            throw new InvalidArgumentException('Audit event type is missing.');
        }

        if ($description === '') {
            throw new InvalidArgumentException('Audit description is missing.');
        }

        if ($user !== null && ! $user->exists) {
            throw new InvalidArgumentException('Audit user must be saved.');
        }

        if ($subject !== null && ! $subject->exists) {
            throw new InvalidArgumentException('Audit subject must be saved.');
        }

        $properties = $this->normalizeProperties($properties);

        $event = new AuditEvent([
            'event_type' => $eventType,
            'description' => $description,
            'properties' => $properties,
            'request_id' => $requestId,
        ]);

        $event->user()->associate($user);

        if ($subject !== null) {
            $event->subject()->associate($subject);
        }

        $event->save();

        return $event;
    }

    /**
     * @param  array<array-key, mixed>|null  $properties
     * @return array<string, scalar|null>|null
     */
    private function normalizeProperties(?array $properties): ?array
    {
        if ($properties === null || $properties === []) {
            return null;
        }

        $normalized = [];

        foreach ($properties as $key => $value) {
            if (! is_string($key) || trim($key) === '') {
                throw new InvalidArgumentException('Audit property key is invalid.');
            }

            if (in_array(strtolower($key), self::SENSITIVE_PROPERTY_KEYS, true)) {
                throw new InvalidArgumentException('Audit property key is sensitive.');
            }

            if (! is_scalar($value) && $value !== null) {
                throw new InvalidArgumentException('Audit property value must be scalar.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
