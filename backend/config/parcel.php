<?php

return [

    /*
     * The partner API never exposes declared_value_paise (docs/06), only a
     * coarse "handle with care" boolean derived from this threshold.
     */
    'high_value_threshold_paise' => (int) env('PARCEL_HIGH_VALUE_THRESHOLD_PAISE', 1000000),

];
