<?php

return [

    /*
     * Fallback defaults for `cancellation.*` app_settings keys (docs/04-state-machine.md).
     * Admins override these at runtime via the app_settings table; these env-driven
     * values only apply when no matching app_settings row exists yet.
     */
    'pre_pickup_cutoff_minutes' => (int) env('CANCELLATION_PRE_PICKUP_CUTOFF_MINUTES', 30),

    'fee_percentage_within_cutoff' => (int) env('CANCELLATION_FEE_PERCENTAGE_WITHIN_CUTOFF', 0),

];
