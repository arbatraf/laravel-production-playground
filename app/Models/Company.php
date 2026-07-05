<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Models\Concerns\HasAuditEvents;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property CompanyType $type
 * @property CompanyStatus $status
 */
#[Fillable(['name', 'type', 'status', 'website', 'email', 'phone'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasAuditEvents, HasFactory, SoftDeletes;

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
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
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
