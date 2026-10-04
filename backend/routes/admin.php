<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryPartnerController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OtpVerificationLogController;
use App\Http\Controllers\Admin\PricingRuleController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\RouteScheduleController;
use App\Http\Controllers\Admin\StationController;
use App\Http\Controllers\Admin\SupportTicketController;
use Illuminate\Support\Facades\Route;

/**
 * All routes here are already prefixed "admin/" and named "admin.*" by the
 * group registered in bootstrap/app.php. Protected routes are additionally
 * gated by the "admin" middleware (EnsureAdminAccess — mirrors the old
 * Filament panel-level canAccessPanel() gate). Fine-grained role checks
 * (e.g. canManageMasterData()) happen per-action via App\Support\AdminAccess.
 */
Route::middleware('guest:web')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('admin')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('cities/{city}/restore', [CityController::class, 'restore'])->name('cities.restore');
    Route::delete('cities/{city}/force-delete', [CityController::class, 'forceDelete'])->name('cities.force-delete');
    Route::resource('cities', CityController::class)->except(['show']);

    Route::post('stations/{station}/restore', [StationController::class, 'restore'])->name('stations.restore');
    Route::delete('stations/{station}/force-delete', [StationController::class, 'forceDelete'])->name('stations.force-delete');
    Route::resource('stations', StationController::class)->except(['show']);

    Route::post('routes/{route}/restore', [RouteController::class, 'restore'])->name('routes.restore');
    Route::delete('routes/{route}/force-delete', [RouteController::class, 'forceDelete'])->name('routes.force-delete');
    Route::resource('routes', RouteController::class)->except(['show']);

    Route::get('routes/{route}/schedules/create', [RouteScheduleController::class, 'create'])->name('routes.schedules.create');
    Route::post('routes/{route}/schedules', [RouteScheduleController::class, 'store'])->name('routes.schedules.store');
    Route::get('routes/{route}/schedules/{schedule}/edit', [RouteScheduleController::class, 'edit'])->name('routes.schedules.edit');
    Route::put('routes/{route}/schedules/{schedule}', [RouteScheduleController::class, 'update'])->name('routes.schedules.update');
    Route::delete('routes/{route}/schedules/{schedule}', [RouteScheduleController::class, 'destroy'])->name('routes.schedules.destroy');

    Route::resource('pricing-rules', PricingRuleController::class)->except(['show']);

    Route::resource('customers', CustomerController::class)->only(['index', 'edit', 'update']);

    Route::post('delivery-partners/{delivery_partner}/restore', [DeliveryPartnerController::class, 'restore'])->name('delivery-partners.restore');
    Route::delete('delivery-partners/{delivery_partner}/force-delete', [DeliveryPartnerController::class, 'forceDelete'])->name('delivery-partners.force-delete');
    Route::post('delivery-partners/{delivery_partner}/reset-password', [DeliveryPartnerController::class, 'resetPassword'])->name('delivery-partners.reset-password');
    Route::resource('delivery-partners', DeliveryPartnerController::class)->except(['show']);

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/recheck', [OrderController::class, 'recheck'])->name('orders.recheck');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/assign-partner', [OrderController::class, 'assignPartner'])->name('orders.assign-partner');
    Route::post('orders/{order}/force-cancel', [OrderController::class, 'forceCancel'])->name('orders.force-cancel');
    Route::post('orders/{order}/mark-disputed', [OrderController::class, 'markDisputed'])->name('orders.mark-disputed');
    Route::post('orders/{order}/approve-refund', [OrderController::class, 'approveRefund'])->name('orders.approve-refund');
    Route::post('orders/{order}/otp/{purpose}/generate', [OrderController::class, 'generateOtp'])->name('orders.otp.generate');

    Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::get('refunds/{refund}', [RefundController::class, 'show'])->name('refunds.show');
    Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
    Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');

    Route::get('support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('support-tickets/{support_ticket}/edit', [SupportTicketController::class, 'edit'])->name('support-tickets.edit');
    Route::put('support-tickets/{support_ticket}', [SupportTicketController::class, 'update'])->name('support-tickets.update');
    Route::post('support-tickets/{support_ticket}/messages', [SupportTicketController::class, 'storeMessage'])->name('support-tickets.messages.store');

    Route::get('otp-logs', [OtpVerificationLogController::class, 'index'])->name('otp-logs.index');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
});
