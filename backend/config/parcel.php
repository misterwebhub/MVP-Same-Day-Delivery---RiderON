<?php

return [

    /*
     * The partner API never exposes declared_value_paise (docs/06), only a
     * coarse "handle with care" boolean derived from this threshold.
     */
    'high_value_threshold_paise' => (int) env('PARCEL_HIGH_VALUE_THRESHOLD_PAISE', 1000000),

    /*
     * City names (case-insensitive, matched against cities.name) where the
     * customer app offers "enter address manually" instead of only a station
     * dropdown — applies to whichever end of the route (pickup or delivery)
     * resolves to one of these cities (e.g. Kanpur->Lucknow gets a manual
     * pickup address, Lucknow->Kanpur gets a manual delivery address).
     * Everywhere else stays station-only. See
     * OrderController::resolveManualAddress().
     */
    'manual_address_cities' => array_filter(array_map(
        'trim',
        explode(',', (string) env('PARCEL_MANUAL_ADDRESS_CITIES', 'Kanpur')),
    )),

];
