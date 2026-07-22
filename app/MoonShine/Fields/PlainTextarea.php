<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use App\MoonShine\Fields\Concerns\StoresPlainText;
use MoonShine\UI\Fields\Textarea;

final class PlainTextarea extends Textarea
{
    use StoresPlainText;

    protected function resolveValue(): mixed
    {
        return $this->escapeValue((string) parent::resolveValue());
    }
}
