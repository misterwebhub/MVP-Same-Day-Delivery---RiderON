<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderActivityLog;
use App\Models\ParcelImage;
use App\Services\Activity\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Customer-side parcel photo upload (docs fraud-prevention addendum): lets
 * the sender attach a real photo of what they're shipping at booking time,
 * so the pickup rider has something concrete to compare the physical
 * parcel against — same idea as the OTP, but for "is this actually what
 * was declared" rather than "is this the right person".
 */
class ParcelPhotoController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function store(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'type' => ['nullable', 'string', 'in:'.ParcelImage::TYPE_PHOTO.','.ParcelImage::TYPE_INVOICE],
        ]);

        $order->loadMissing('parcel.images');
        abort_unless($order->parcel !== null, 422, 'This order has no parcel record to attach a photo to.');

        $path = $request->file('photo')->store('parcels', 'public');

        ParcelImage::create([
            'parcel_id' => $order->parcel->id,
            'storage_path' => $path,
            'type' => $request->input('type', ParcelImage::TYPE_PHOTO),
            'created_at' => now(),
        ]);

        // Customer side stays IP-only (no location permission prompt) — the
        // GPS + distance-from-station signal is rider-side only.
        $this->activityLogger->log(
            $order,
            OrderActivityLog::EVENT_PARCEL_PHOTO_UPLOADED,
            OrderActivityLog::ACTOR_CUSTOMER,
            $request->user()->id,
            $request,
        );

        $order->load(['parcel.images', 'route.originStation', 'route.destinationStation', 'routeSchedule', 'payments', 'otpVerifications']);

        return $this->success(new OrderResource($order), 'Photo attached.');
    }

    public static function absoluteUrl(string $storagePath): string
    {
        return url(Storage::disk('public')->url($storagePath));
    }
}
