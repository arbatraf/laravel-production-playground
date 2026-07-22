<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Concerns\HasAuditEvents;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property TaskStatus $status
 * @property TaskPriority $priority
 */
#[Fillable(['title', 'description', 'priority', 'due_at'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasAuditEvents, HasFactory, SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return MorphMany<Note, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    /**
     * @param  Builder<Task>  $query
     */
    public function scopeAssignedTo(Builder $query, User $user): void
    {
        $query->where('assigned_to_user_id', $user->getKey());
    }

    /**
     * @param  Builder<Task>  $query
     */
    public function scopeStatus(Builder $query, TaskStatus $status): void
    {
        $query->where('status', $status->value);
    }

    /**
     * @param  Builder<Task>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [
            TaskStatus::Open->value,
            TaskStatus::InProgress->value,
            TaskStatus::Waiting->value,
        ]);
    }

    /**
     * @param  Builder<Task>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->open()->where('due_at', '<', now());
    }

    /**
     * @param  Builder<Task>  $query
     */
    public function scopeDueToday(Builder $query): void
    {
        $today = now()->startOfDay();

        $query
            ->open()
            ->where('due_at', '>=', $today)
            ->where('due_at', '<', $today->copy()->addDay());
    }
}
