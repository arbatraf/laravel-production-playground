<?php

declare(strict_types=1);

namespace App\MoonShine\Forms;

use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FormContract;
use MoonShine\Support\Traits\Makeable;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\Text;

final class LoginForm implements FormContract
{
    use Makeable;

    public function __construct(
        private readonly string $action,
        private readonly CoreContract $core,
    ) {}

    public function __invoke(): FormBuilderContract
    {
        return FormBuilder::make()
            ->class('authentication-form')
            ->action($this->action)
            ->errorsAbove(false)
            ->fields([
                Text::make('Email', 'username')
                    ->required()
                    ->customAttributes([
                        'autofocus' => true,
                        'autocomplete' => 'username',
                    ]),
                Password::make($this->core->getTranslator()->get('moonshine::ui.login.password'), 'password')
                    ->required()
                    ->customAttributes(['autocomplete' => 'current-password']),
            ])
            ->submit($this->core->getTranslator()->get('moonshine::ui.login.login'), [
                'class' => 'btn-primary btn-lg w-full',
            ]);
    }
}
