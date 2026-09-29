<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['destination_id', 'name', 'slug', 'type', 'capacity', 'is_seated', 'seat_layout', 'size_sqm', 'description', 'is_active'])]
class Venue extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_seated' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Destination, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * The venue's fixed seat map, ordered for display (row by row, seat by seat).
     *
     * @return HasMany<Seat, $this>
     */
    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class)->orderBy('sort_order')->orderBy('row_label')->orderBy('seat_number');
    }
}
