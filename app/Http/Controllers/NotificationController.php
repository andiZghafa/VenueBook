<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Destination;
use App\Services\UpcomingEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected UpcomingEventService $upcomingEventService
    ) {}

    /**
     * Get upcoming event notifications payload for the bell dropdown.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only admins may filter by destination; sales users are locked to their destination(s)
        $destinationId = $user->isAdmin() && $request->filled('destination_id')
            ? (int) $request->input('destination_id')
            : null;

        $payload = $this->upcomingEventService->getNotificationsPayload($user, $destinationId);

        $destinations = $user->isAdmin()
            ? Destination::orderBy('name')->get(['id', 'name'])
            : [];

        return response()->json([
            ...$payload,
            'destinations' => $destinations,
            'is_admin' => $user->isAdmin(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        $this->upcomingEventService->markAsRead($request->user(), $booking);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all visible upcoming events as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $destinationId = $user->isAdmin() && $request->filled('destination_id')
            ? (int) $request->input('destination_id')
            : null;

        $count = $this->upcomingEventService->markAllAsRead($user, $destinationId);

        return response()->json([
            'success' => true,
            'marked' => $count,
        ]);
    }

    /**
     * Mark an event as read and redirect directly to its detail page.
     * Friendly 404 page if deleted or missing.
     */
    public function go(Request $request, string $booking): View|Response|RedirectResponse
    {
        $item = Booking::withTrashed()->find($booking);

        if (! $item || $item->trashed()) {
            return response()->view('bookings.not_found', [
                'booking' => $item,
            ], 404);
        }

        $this->authorize('view', $item);

        $this->upcomingEventService->markAsRead($request->user(), $item);

        return redirect()->route('bookings.show', $item);
    }
}
