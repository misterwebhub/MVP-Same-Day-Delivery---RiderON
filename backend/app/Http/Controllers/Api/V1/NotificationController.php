<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\RegisterDeviceRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Device;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->when($request->boolean('unread'), fn ($query) => $query->unread())
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 20));

        return $this->success([
            'items' => NotificationResource::collection($notifications->items()),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

        return $this->success(new NotificationResource($notification), 'Notification marked as read.');
    }

    public function registerDevice(RegisterDeviceRequest $request): JsonResponse
    {
        $device = Device::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'fcm_token' => $request->string('token')->toString(),
            ],
            [
                'platform' => $request->string('platform')->toString(),
                'last_seen_at' => now(),
            ],
        );

        return $this->success([
            'id' => $device->id,
            'platform' => $device->platform,
        ], 'Device registered.');
    }
}
