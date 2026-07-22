<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Resources\AuditEvent\AuditEventResource;
use App\MoonShine\Resources\Company\CompanyResource;
use App\MoonShine\Resources\Contact\ContactResource;
use App\MoonShine\Resources\Note\NoteResource;
use App\MoonShine\Resources\Task\TaskResource;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core->resources([
            CompanyResource::class,
            ContactResource::class,
            TaskResource::class,
            NoteResource::class,
            UserResource::class,
            AuditEventResource::class,
        ])->pages($core->getConfig()->getPages());
    }
}
