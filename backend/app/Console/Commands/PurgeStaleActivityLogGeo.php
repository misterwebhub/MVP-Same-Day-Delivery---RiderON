<?php

namespace App\Console\Commands;

use App\Models\OrderActivityLog;
use Illuminate\Console\Command;

/**
 * Retention job for order_activity_logs (docs fraud-prevention addendum):
 * rows older than config('activity.retention_days') have their raw
 * ip_address/latitude/longitude nulled out, but distance_from_target_meters
 * (the derived fraud-analysis signal) is kept forever, along with the event/
 * actor/timestamp trail itself. Scheduled daily in routes/console.php.
 */
class PurgeStaleActivityLogGeo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-stale-activity-log-geo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge raw IP/GPS data from order_activity_logs rows older than the configured retention window, keeping the computed distance-from-station value.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = (int) config('activity.retention_days');

        if ($retentionDays <= 0) {
            $this->info('Retention purge disabled (activity.retention_days <= 0).');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($retentionDays);

        $affected = OrderActivityLog::query()
            ->where('created_at', '<', $cutoff)
            ->where(function ($query) {
                $query->whereNotNull('ip_address')
                    ->orWhereNotNull('latitude')
                    ->orWhereNotNull('longitude');
            })
            ->update([
                'ip_address' => null,
                'latitude' => null,
                'longitude' => null,
            ]);

        $this->info("Purged raw IP/GPS from {$affected} activity log row(s) older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
