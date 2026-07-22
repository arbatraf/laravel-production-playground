<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Concerns;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait LimitsMassDeletion
{
    private const int MASS_DELETE_LIMIT = 100;

    public function massDelete(array $ids): void
    {
        if (count($ids) > self::MASS_DELETE_LIMIT) {
            throw ValidationException::withMessages([
                'ids' => 'Select up to 100 records.',
            ]);
        }

        Validator::make(
            ['ids' => $ids],
            [
                'ids' => ['required', 'array', 'list', 'min:1'],
                'ids.*' => ['integer', 'distinct'],
            ],
        )->validate();

        $this->getDataInstance()->getConnection()->transaction(
            function () use ($ids): void {
                parent::massDelete($ids);
            },
        );
    }
}
