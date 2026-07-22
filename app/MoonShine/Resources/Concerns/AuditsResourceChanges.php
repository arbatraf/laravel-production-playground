<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Concerns;

use App\Actions\Audit\RecordBackofficeResourceAuditAction;
use App\Http\Middleware\AssignRequestId;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use MoonShine\Contracts\Core\DependencyInjection\FieldsContract;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Core\Exceptions\ResourceException;
use MoonShine\Laravel\MoonShineAuth;

trait AuditsResourceChanges
{
    public function save(DataWrapperContract $item, ?FieldsContract $fields = null): DataWrapperContract
    {
        return $item->getOriginal()->getConnection()->transaction(
            fn (): DataWrapperContract => parent::save($item, $fields),
        );
    }

    public function delete(DataWrapperContract $item, ?FieldsContract $fields = null): bool
    {
        return $item->getOriginal()->getConnection()->transaction(function () use ($item, $fields): bool {
            if (! parent::delete($item, $fields)) {
                throw new ResourceException(sprintf(
                    '%s deletion failed.',
                    class_basename($item->getOriginal()),
                ));
            }

            return true;
        });
    }

    protected function afterCreated(DataWrapperContract $item): DataWrapperContract
    {
        $this->auditResource($item->getOriginal(), 'created');

        return $item;
    }

    protected function afterUpdated(DataWrapperContract $item): DataWrapperContract
    {
        $this->auditResource($item->getOriginal(), 'updated');

        return $item;
    }

    protected function beforeDeleting(DataWrapperContract $item): DataWrapperContract
    {
        $subject = $item->getOriginal();

        if (! in_array(SoftDeletes::class, class_uses_recursive($subject), true)) {
            $this->auditResource($subject, 'deleted');
        }

        return $item;
    }

    protected function afterDeleted(DataWrapperContract $item): DataWrapperContract
    {
        $subject = $item->getOriginal();

        if (in_array(SoftDeletes::class, class_uses_recursive($subject), true)) {
            $this->auditResource($subject, 'deleted');
        }

        return $item;
    }

    private function auditResource(Model $subject, string $operation): void
    {
        $user = MoonShineAuth::getGuard()->user();

        if (! $user instanceof User) {
            return;
        }

        $action = app(RecordBackofficeResourceAuditAction::class);
        $requestId = AssignRequestId::current(request());

        if ($operation === 'created') {
            $action->created($subject, $user, $requestId);

            return;
        }

        if ($operation === 'updated') {
            $action->updated($subject, $user, $requestId);

            return;
        }

        if ($operation === 'deleted') {
            $action->deleted($subject, $user, $requestId);

            return;
        }

        throw new LogicException('Unsupported resource audit operation.');
    }
}
