<?php

namespace App\Console\Commands;

use App\Constants\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\StateMachines\OrderStateMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Promotes DELIVERED orders to COMPLETED once their dispute grace window
 * (config('order.auto_complete_minutes'), docs/04-state-machine.md) has
 * elapsed. Orders whose delivery OTP was verified while
 * auto_complete_minutes <= 0 are already completed synchronously in
 * OrderOtpVerificationService and never reach this job; this only handles
 * the deferred case (a positive grace window) — previously "not yet built",
 * which left every DELIVERED order stuck there forever regardless of the
 * configured window. Scheduled frequently in routes/console.php with
 * withoutOverlapping, per docs/04's "Scheduled jobs" note.
 */
class AutoCompleteDeliveries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-complete-deliveries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-complete DELIVERED orders whose dispute grace window (order.auto_complete_minutes) has elapsed.';

    public function handle(OrderStateMachine $stateMachine): int
    {
        $minutes = (int) config('order.auto_complete_minutes');
        $cutoff = now()->subMinutes(max($minutes, 0));

        // Backfill: delivered_at was never populated before this fix, so any
        // order already sitting in DELIVERED from before this deploy has a
        // null delivered_at and would otherwise never be picked up below.
        // Recover the real delivery time from its status-change history.
        $this->backfillMissingDeliveredAt();

        $orders = Order::query()
            ->where('status', OrderStatus::DELIVERED)
            ->whereNotNull('delivered_at')
            ->where('delivered_at', '<=', $cutoff)
            ->get();

        $completed = 0;

        foreach ($orders as $order) {
            $stateMachine->transitionIfNotAlready(
                $order,
                OrderStatus::COMPLETED,
                OrderStatusHistory::ACTOR_SYSTEM,
            );
            $completed++;
        }

        $this->info("Auto-completed {$completed} order(s) past their {$minutes}-minute grace window.");

        return self::SUCCESS;
    }

    private function backfillMissingDeliveredAt(): void
    {
        $stuck = Order::query()
            ->where('status', OrderStatus::DELIVERED)
            ->whereNull('delivered_at')
            ->pluck('id');

        if ($stuck->isEmpty()) {
            return;
        }

        $historyByOrder = DB::table('order_status_history')
            ->whereIn('order_id', $stuck)
            ->where('to_status', OrderStatus::DELIVERED)
            ->orderByDesc('created_at')
            ->get(['order_id', 'created_at'])
            ->unique('order_id')
            ->keyBy('order_id');

        $backfilled = 0;

        foreach ($stuck as $orderId) {
            $deliveredAt = $historyByOrder->get($orderId)?->created_at ?? now();

            Order::query()->whereKey($orderId)->update(['delivered_at' => $deliveredAt]);
            $backfilled++;
        }

        if ($backfilled > 0) {
            $this->info("Backfilled delivered_at for {$backfilled} pre-existing DELIVERED order(s) from history.");
        }
    }
}
