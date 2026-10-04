<?php

namespace App\Services\Notifications;

use App\Models\Device;
use App\Models\Notification;
use App\Models\User;
use App\Services\Push\PushProvider;
use Illuminate\Support\Facades\Log;

/**
 * Single choke point for "something happened, who needs to know" — turns a
 * domain event into a persisted Notification row (so the customer/partner
 * app and the admin activity feed all read from the same table) and, for
 * push-channel notifications, best-effort delivery via PushProvider.
 *
 * Real push/WhatsApp delivery is intentionally a placeholder in dev
 * (MockPushProvider just logs — see docs on SmsProvider/MockSmsProvider for
 * the same pattern already used for OTP SMS). The triggering logic, the
 * persisted Notification row, and admin visibility are real; only the
 * outbound wire call is stubbed until a real FCM/WhatsApp Business account
 * is wired up.
 */
class NotificationService
{
    public function __construct(private readonly PushProvider $pushProvider)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $body,
        array $data = [],
        string $channel = Notification::CHANNEL_PUSH,
    ): Notification {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'channel' => $channel,
        ]);

        if ($channel === Notification::CHANNEL_PUSH) {
            $this->dispatchPush($userId, $title, $body, $data, $notification);
        }

        return $notification;
    }

    /**
     * Fan-out to every admin-console user (admin/ops/support roles) so "all
     * activity" is visible from the admin notifications list, regardless of
     * which staff member is looking.
     *
     * @param  array<string, mixed>  $data
     */
    public function notifyAdmins(string $type, string $title, string $body, array $data = []): void
    {
        User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPS, User::ROLE_SUPPORT])
            ->pluck('id')
            ->each(fn (int $id) => $this->notifyUser($id, $type, $title, $body, $data, Notification::CHANNEL_IN_APP));
    }

    /**
     * Placeholder for messaging a phone number that has no app account at
     * all — e.g. the parcel receiver, who never logs into anything. Real
     * WhatsApp/SMS delivery is a driver-level concern (mirrors
     * App\Services\Sms\SmsProvider); this just centralizes and audit-logs
     * the "who do we tell when an OTP is regenerated" business logic in one
     * place rather than scattering ad-hoc Log::info() calls across
     * controllers.
     */
    public function notifyReceiverByPhone(string $phone, string $message): void
    {
        Log::info('[NotificationService] Message to non-app phone number (placeholder — no WhatsApp/SMS driver wired up for this yet)', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dispatchPush(int $userId, string $title, string $body, array $data, Notification $notification): void
    {
        $device = Device::query()->where('user_id', $userId)->latest('last_seen_at')->first();

        if ($device === null) {
            Log::info('[NotificationService] No registered device for push — skipping delivery (row still persisted for in-app list)', [
                'user_id' => $userId,
            ]);

            return;
        }

        $result = $this->pushProvider->send($device->fcm_token, $title, $body, $data);

        if ($result->success) {
            $notification->forceFill(['sent_at' => now()])->save();
        }
    }
}
