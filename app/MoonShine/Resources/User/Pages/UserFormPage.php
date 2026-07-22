<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\User\Pages;

use App\Enums\UserRole;
use App\Models\User;
use App\MoonShine\Fields\PlainEmail;
use App\MoonShine\Fields\PlainText;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\PasswordRepeat;
use MoonShine\UI\Fields\Select;

/**
 * @extends FormPage<UserResource>
 */
final class UserFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                PlainText::make('Name', 'name')->required(),
                PlainEmail::make('Email', 'email')->required(),
                Select::make('Role', 'role')->options([
                    UserRole::Admin->value => UserRole::Admin->label(),
                    UserRole::Manager->value => UserRole::Manager->label(),
                    UserRole::Viewer->value => UserRole::Viewer->label(),
                ])->required(),
                Password::make('Password', 'password')
                    ->customAttributes(['autocomplete' => 'new-password'])
                    ->eye(),
                PasswordRepeat::make('Confirm password', 'password_confirmation')
                    ->customAttributes(['autocomplete' => 'new-password'])
                    ->eye(),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(User::class)->ignoreModel($item->getOriginal()),
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => [
                ...$item->getKey() === null ? ['required'] : ['sometimes', 'nullable'],
                PasswordRule::defaults(),
                'confirmed',
            ],
        ];
    }
}
