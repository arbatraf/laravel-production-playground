<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CompanyType $type
 * @property CompanyStatus $status
 */
#[Fillable(['name', 'type', 'status', 'website', 'email', 'phone'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => CompanyType::class,
            'status' => CompanyStatus::class,
        ];
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @param  Builder<Company>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', CompanyStatus::Active->value);
    }

    /**
     * @param  Builder<Company>  $query
     */
    public function scopeStatus(Builder $query, CompanyStatus $status): void
    {
        $query->where('status', $status->value);
    }

    /**
     * @param  Builder<Company>  $query
     */
    public function scopeType(Builder $query, CompanyType $type): void
    {
        $query->where('type', $type->value);
    }
}
