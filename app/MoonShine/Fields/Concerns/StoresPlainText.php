<?php

declare(strict_types=1);

namespace App\MoonShine\Fields\Concerns;

trait StoresPlainText
{
    protected function prepareRequestValue(mixed $value): mixed
    {
        return $value;
    }
}
