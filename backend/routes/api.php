<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CityController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderCallController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderOtpController;
use App\Http\Controllers\Api\V1\ParcelPhotoController;
use App\Http\Controllers\Api\V1\PartnerAssignmentController;
use App\Http\Controllers\Api\V1\PartnerEarningsController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PricingController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProhibitedItemController;
use App\Http\Controllers\Api\V1\RouteController;
use App\Http\Controllers\Api\V1\SupportFaqController;
use App\Http\Controllers\Api\V1\SupportTicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('otp/request', [AuthController::class, 'requestOtp'])->middleware('throttle:otp-request');
        Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('partner/login', [AuthController::class, 'partnerLogin']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('profile', [AuthController::class, 'completeProfile']);
        });
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    Route::get('cities', [CityController::class, 'index']);
    Route::get('cities/{city}/stations', [CityController::class, 'stations']);

    Route::get('routes/popular', [RouteController::class, 'popular']);
    Route::get('routes/{route}/schedules', [RouteController::class, 'schedules']);
    Route::get('routes/{route}', [RouteController::class, 'show']);
    Route::get('routes', [RouteController::class, 'index']);

    Route::post('pricing/quote', [PricingController::class, 'quote']);

    Route::get('prohibited-items', [ProhibitedItemController::class, 'index']);

    Route::get('support/faq', [SupportFaqController::class, 'index']);

    Route::post('payments/webhook/razorpay', [PaymentController::class, 'webhook']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('devices', [NotificationController::class, 'registerDevice']);

        Route::post('support/tickets', [SupportTicketController::class, 'store']);
        Route::get('support/tickets', [SupportTicketController::class, 'index']);
        Route::get('support/tickets/{ticket}', [SupportTicketController::class, 'show']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::patch('profile', [ProfileController::class, 'update']);
        Route::get('profile/saved-contacts', [ProfileController::class, 'savedContacts']);
        Route::post('profile/saved-contacts', [ProfileController::class, 'storeSavedContact']);
        Route::delete('profile/saved-contacts/{contact}', [ProfileController::class, 'destroySavedContact']);

        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store'])->middleware('idempotent');
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('idempotent');

        Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->middleware('idempotent');
        Route::get('payments/{payment}/status', [PaymentController::class, 'status']);

        Route::post('orders/{order}/parcel/photos', [ParcelPhotoController::class, 'store']);

        Route::middleware('role:partner')->group(function () {
            Route::post('orders/{order}/otp/{purpose}/verify', [OrderOtpController::class, 'verify'])
                ->whereIn('purpose', ['pickup', 'delivery']);

            Route::post('orders/{order}/call/{target}', [OrderCallController::class, 'initiate'])
                ->whereIn('target', ['sender', 'receiver']);

            Route::get('partner/earnings', [PartnerEarningsController::class, 'index']);

            Route::prefix('partner/assignments')->group(function () {
                Route::get('/', [PartnerAssignmentController::class, 'index']);
                Route::get('unassigned', [PartnerAssignmentController::class, 'unassigned']);
                Route::get('{order}', [PartnerAssignmentController::class, 'show']);
                Route::post('{order}/accept', [PartnerAssignmentController::class, 'accept'])->middleware('idempotent');
                Route::post('{order}/arrived-pickup', [PartnerAssignmentController::class, 'arrivedPickup']);
                Route::post('{order}/start-transit', [PartnerAssignmentController::class, 'startTransit']);
                Route::post('{order}/arrived-destination', [PartnerAssignmentController::class, 'arrivedDestination']);
                Route::post('{order}/otp/{purpose}/regenerate', [PartnerAssignmentController::class, 'regenerateOtp'])
                    ->whereIn('purpose', ['pickup', 'delivery']);
                Route::post('{order}/pickup-photo', [PartnerAssignmentController::class, 'uploadPickupPhoto']);
                Route::post('{order}/delivery-photo', [PartnerAssignmentController::class, 'uploadDeliveryPhoto']);
            });
        });
    });
});
