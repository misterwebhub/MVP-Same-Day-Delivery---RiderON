<?php

return [

    /*
     * How long (in days) raw IP address and GPS coordinates are kept on
     * order_activity_logs rows before being purged by
     * App\Console\Commands\PurgeStaleActivityLogGeo. The computed
     * distance_from_target_meters value is NOT purged — it's the
     * fraud-analysis signal derived from the GPS point, and keeping it
     * indefinitely (while dropping the raw, more sensitive lat/lng/IP) is
     * the whole point of this retention policy.
     */
    'retention_days' => (int) env('ACTIVITY_LOG_RETENTION_DAYS', 180),

];
