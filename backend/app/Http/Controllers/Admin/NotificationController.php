<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only "all activity" feed for admin — every Notification row ever
 * created (customer/partner push, receiver SMS placeholder logs stay in
 * Laravel logs, but anything that touched the notifications table, e.g.
 * OTP regenerated / new order assigned, shows up here regardless of which
 * user it was addressed to). Mirrors OtpVerificationLogController's
 * read-only-list pattern.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notification::query()
            ->with('user')
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->query('channel'), fn ($q, $channel) => $q->where('channel', $channel))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $types = Notification::query()->select('type')->distinct()->orderBy('type')->pluck('type');

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'types' => $types,
            'filters' => $request->only(['type', 'channel']),
        ]);
    }
}
