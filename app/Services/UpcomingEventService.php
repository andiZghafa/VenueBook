<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\NotificationRead;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class UpcomingEventService
{
    /**
     * Number of days from today for upcoming events.
     */
    public function getUpcomingDays(): int
    {
        return (int) config('booking.upcoming_days', 7);
    }

    /**
     * Timezone for date comparisons (Asia/Jakarta / UTC+7).
     */
    public function getTimezone(): string
    {
        return (string) config('booking.timezone', 'Asia/Jakarta');
    }

    /**
     * Get start of today in the application's event timezone.
     */
    public function getToday(?CarbonInterface $referenceDate = null): Carbon
    {
        $tz = $this->getTimezone();

        if ($referenceDate !== null) {
            return Carbon::parse($referenceDate)->setTimezone($tz)->startOfDay();
        }

        return Carbon::now($tz)->startOfDay();
    }

    /**
     * Get the [today, today + N days] window.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function getUpcomingWindow(?CarbonInterface $referenceDate = null): array
    {
        $today = $this->getToday($referenceDate);
        $windowEnd = $today->copy()->addDays($this->getUpcomingDays())->endOfDay();

        return [$today, $windowEnd];
    }

    /**
     * Single shared query for "upcoming events visible to this user".
     *
     * Rules enforced:
     * 1. Window: from today through today + N days (inclusive).
     * 2. Past events never count (end_date >= today).
     * 3. Cancelled/deleted events never count.
     * 4. Sales users only see events at venues in their destination(s).
     *    If a sales user has no destination assigned, returns empty results.
     * 5. Admins see all destinations, with optional destination filter.
     *
     * @param  Builder<Booking>|null  $query
     * @return Builder<Booking>
     */
    public function upcomingEventsQuery(
        User $user,
        ?int $destinationId = null,
        ?CarbonInterface $referenceDate = null,
        ?Builder $query = null
    ): Builder {
        [$today, $windowEnd] = $this->getUpcomingWindow($referenceDate);

        $builder = ($query ?? Booking::query())
            ->where('status', '!=', 'cancelled')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('start_date', '<=', $windowEnd)
            ->with(['venue.destination', 'eventType', 'client', 'pic']);

        if (! $user->isAdmin()) {
            $destinations = $user->destinationIds();
            if (empty($destinations)) {
                // If a sales user has no destination assigned, show no warnings
                return $builder->whereRaw('0 = 1');
            }

            $builder->whereHas('venue', fn ($v) => $v->whereIn('destination_id', $destinations));
        } elseif ($destinationId !== null) {
            $builder->whereHas('venue', fn ($v) => $v->where('destination_id', $destinationId));
        }

        return $builder;
    }

    /**
     * Get upcoming events visible to the given user, sorted soonest first.
     *
     * @return Collection<int, Booking>
     */
    public function getUpcomingEvents(
        User $user,
        ?int $destinationId = null,
        ?CarbonInterface $referenceDate = null
    ): Collection {
        return $this->upcomingEventsQuery($user, $destinationId, $referenceDate)
            ->orderBy('start_date')
            ->orderBy('load_at')
            ->get();
    }

    /**
     * Builds the payload for the header bell notification dropdown.
     *
     * @return array{
     *     unread_count: int,
     *     badge: string,
     *     items: array<int, array<string, mixed>>
     * }
     */
    public function getNotificationsPayload(
        User $user,
        ?int $destinationId = null,
        ?CarbonInterface $referenceDate = null
    ): array {
        $events = $this->getUpcomingEvents($user, $destinationId, $referenceDate);
        $today = $this->getToday($referenceDate);

        // Fetch read records for this user for these events
        $readRecords = NotificationRead::where('user_id', $user->id)
            ->whereIn('booking_id', $events->pluck('id'))
            ->get()
            ->keyBy('booking_id');

        $unreadCount = 0;
        $items = [];

        foreach ($events as $booking) {
            $currentHash = $booking->scheduleHash();
            $readRecord = $readRecords->get($booking->id);

            // If schedule changed after being read, schedule_hash won't match, so it's unread again
            $isRead = $readRecord !== null && $readRecord->schedule_hash === $currentHash;

            if (! $isRead) {
                $unreadCount++;
            }

            // Relative label
            $eventStart = $booking->start_date->copy()->startOfDay();
            $diffDays = (int) $today->diffInDays($eventStart, false);

            if ($diffDays <= 0) {
                $relativeLabel = 'Hari ini';
                $isUrgent = true;
            } elseif ($diffDays === 1) {
                $relativeLabel = 'Besok';
                $isUrgent = true;
            } else {
                $relativeLabel = "H-{$diffDays}";
                $isUrgent = false;
            }

            $dateLabel = $booking->start_date->eq($booking->end_date)
                ? $booking->start_date->translatedFormat('d M Y')
                : $booking->start_date->translatedFormat('d M Y').' – '.$booking->end_date->translatedFormat('d M Y');

            $timeLabel = $booking->load_at ? $booking->load_at->translatedFormat('H:i').' WIB' : null;

            $items[] = [
                'id' => $booking->id,
                'title' => $booking->title,
                'venue' => $booking->venue->name,
                'destination' => $booking->venue->destination->name,
                'show_destination' => $user->isAdmin(),
                'date_label' => $dateLabel,
                'time_label' => $timeLabel,
                'relative_label' => $relativeLabel,
                'is_urgent' => $isUrgent,
                'is_read' => $isRead,
                'url' => route('bookings.show', $booking),
                'go_url' => route('notifications.go', $booking),
                'read_url' => route('notifications.read', $booking),
            ];
        }

        return [
            'unread_count' => $unreadCount,
            'badge' => $unreadCount > 9 ? '9+' : (string) $unreadCount,
            'items' => $items,
        ];
    }

    /**
     * Mark a single event as read for the user with the event's current schedule hash.
     */
    public function markAsRead(User $user, Booking $booking): void
    {
        NotificationRead::updateOrCreate(
            ['user_id' => $user->id, 'booking_id' => $booking->id],
            [
                'schedule_hash' => $booking->scheduleHash(),
                'read_at' => now(),
            ]
        );
    }

    /**
     * Mark all upcoming events visible to the user as read.
     */
    public function markAllAsRead(User $user, ?int $destinationId = null): int
    {
        $events = $this->getUpcomingEvents($user, $destinationId);
        $count = 0;

        foreach ($events as $booking) {
            NotificationRead::updateOrCreate(
                ['user_id' => $user->id, 'booking_id' => $booking->id],
                [
                    'schedule_hash' => $booking->scheduleHash(),
                    'read_at' => now(),
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Clean up notification read records for events that have already ended.
     */
    public function cleanupPastReads(): int
    {
        $today = $this->getToday();

        return NotificationRead::whereHas('booking', fn ($q) => $q->whereDate('end_date', '<', $today))->delete();
    }
}
