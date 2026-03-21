<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Ticket extends Model
{
    public const STATUS_OPEN = 'Open';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_RESOLVED = 'Resolved';
    public const STATUS_CLOSED = 'Closed';

    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'requester_name',
        'requester_contact',
        'title',
        'description',
        'category_id',
        'status',
        'assignee_id',
        'created_by',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (! $ticket->status) {
                $ticket->status = self::STATUS_OPEN;
            }

            if (! $ticket->view_key) {
                $ticket->view_key = Str::random(40);
            }
        });

        static::created(function (Ticket $ticket): void {
            if (! $ticket->ticket_code) {
                $ticket->updateQuietly([
                    'ticket_code' => sprintf('IT-%06d', $ticket->id),
                ]);
            }
        });
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('ticket_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('requester_name', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($filters['category_id'] ?? null, fn (Builder $builder, string $categoryId) => $builder->where('category_id', $categoryId))
            ->when($filters['assignee_id'] ?? null, function (Builder $builder, string $assigneeId): void {
                if ($assigneeId === 'unassigned') {
                    $builder->whereNull('assignee_id');

                    return;
                }

                $builder->where('assignee_id', $assigneeId);
            });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class)->latest();
    }

    public function relatedHandlers(): Collection
    {
        $users = collect();

        if ($this->relationLoaded('assignee') && $this->assignee) {
            $users->push($this->assignee);
        } elseif ($this->assignee_id) {
            $users->push($this->assignee()->first());
        }

        $activities = $this->relationLoaded('activities')
            ? $this->activities
            : $this->activities()->with('actor')->get();

        return $users
            ->merge(
                $activities
                    ->whereIn('action_type', ['ticket_claimed', 'comment_added', 'status_changed'])
                    ->pluck('actor')
                    ->filter()
            )
            ->unique('id')
            ->values();
    }
}
