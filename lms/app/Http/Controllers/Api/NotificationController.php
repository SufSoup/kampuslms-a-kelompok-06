<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return NotificationResource::collection($notifications)
            ->additional([
                'meta' => [
                    'unread_count' => $request->user()->unreadNotifications()->count(),
                ],
            ]);
    }

    public function markAsRead(Request $request, string $notification): NotificationResource
    {
        // Scope the lookup to the signed-in user so another user's ID cannot be used.
        $userNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $userNotification->markAsRead();

        return new NotificationResource($userNotification->refresh());
    }
}
