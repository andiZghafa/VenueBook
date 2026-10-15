<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable([
    'code', 'venue_id', 'event_type_id', 'client_id', 'organizer_id', 'pic_user_id', 'created_by',
    'title', 'start_date', 'end_date', 'load_at', 'unload_at', 'guest_count', 'status', 'notes', 'price',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'load_at' => 'datetime',
            'unload_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    /**
     * When the booked date range itself begins, ignoring any crew load-in time.
     */
    public function eventStart(): Carbon
    {
        return $this->start_date->copy()->startOfDay();
    }

    /**
     * When the booked date range itself ends, ignoring any crew load-out time.
     */
    public function eventEnd(): Carbon
    {
        return $this->end_date->copy()->endOfDay();
    }

    /**
     * True if right now falls in the crew's load-in window (before the booked dates
     * start) or load-out window (after the booked dates end) — i.e. the venue is still
     * occupied by setup/teardown even though the "event" itself hasn't started or is over.
     */
    public function isLoadingOrUnloading(): bool
    {
        $now = now();

        $stillLoadingIn = $this->load_at && $now->betweenIncluded($this->load_at, $this->eventStart());
        $stillLoadingOut = $this->unload_at && $now->betweenIncluded($this->eventEnd(), $this->unload_at);

        return $stillLoadingIn || $stillLoadingOut;
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Seat assignments (booked or blocked) for this specific show/date, scoped to this booking
     * so the same physical seat can be independently booked across different bookings.
     *
     * @return HasMany<BookingSeat, $this>
     */
    public function bookingSeats(): HasMany
    {
        return $this->hasMany(BookingSeat::class);
    }

    /**
     * @return BelongsTo<EventType, $this>
     */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'organizer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<Booking>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Booking>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['destination_id'] ?? null, fn ($q, $id) => $q->whereHas('venue', fn ($v) => $v->where('destination_id', $id)))
            ->when($filters['venue_id'] ?? null, fn ($q, $id) => $q->where('venue_id', $id))
            ->when($filters['event_type_id'] ?? null, fn ($q, $id) => $q->where('event_type_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['pic_user_id'] ?? null, fn ($q, $id) => $q->where('pic_user_id', $id))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('organizer', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('venue', fn ($v) => $v->where('name', 'like', "%{$term}%"));
                });
            });
    }

    /**
     * @return HasMany<NotificationRead, $this>
     */
    public function notificationReads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }

    /**
     * Compute a hash of date/time fields to detect schedule changes.
     */
    public function scheduleHash(): string
    {
        return md5(
            ($this->start_date?->format('Y-m-d') ?? '').'|'.
            ($this->end_date?->format('Y-m-d') ?? '').'|'.
            ($this->load_at?->format('Y-m-d H:i:s') ?? '').'|'.
            ($this->unload_at?->format('Y-m-d H:i:s') ?? '')
        );
    }
}
