<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Every authenticated user (admin or sales, any destination) can browse and view bookings.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return true;
    }

    /**
     * Anyone can open the create form; the controller scopes which venues a sales user may pick.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Admins manage every booking; sales users are limited to their own destination's bookings.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $user->destination_id === $booking->venue->destination_id;
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $this->update($user, $booking);
    }
}
