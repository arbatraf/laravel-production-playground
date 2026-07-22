<?php

declare(strict_types=1);

namespace App\MoonShine\Fields;

use App\MoonShine\Fields\Concerns\StoresPlainText;
use MoonShine\UI\Fields\Email;

final class PlainEmail extends Email
{
    use StoresPlainText;
}
