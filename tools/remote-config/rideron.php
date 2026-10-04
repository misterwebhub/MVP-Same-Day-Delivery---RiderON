<?php

/**
 * Remote base-URL config for the RiderON mobile apps (customer + partner).
 *
 * Deploy: upload this single file to the web root of impixoexports.com, so
 * it's reachable at https://impixoexports.com/rideron.php — it is a
 * standalone script, not part of the Laravel app, and needs no framework or
 * dependencies on the host.
 *
 * Called by apps/customer/src/services/httpClient.ts and
 * apps/partner/src/services/httpClient.ts as:
 *   GET https://impixoexports.com/rideron.php?env=development
 *   GET https://impixoexports.com/rideron.php?env=production
 *
 * Returns which backend host the app should point at for that environment.
 * Centralizing this here means the backend host can change (redeploy, new
 * domain, swapped box) without shipping a new app build — the app just asks
 * again on next launch and caches the answer on-device.
 *
 * To repoint an environment, edit only the two URLs below and re-upload —
 * nothing else needs to change.
 */

header('Content-Type: application/json; charset=utf-8');

// Same-origin only in practice (native app fetch, not a browser page), but
// harmless to allow cross-origin reads of this single public config value.
header('Access-Control-Allow-Origin: *');

$baseUrls = [
    'production' => 'https://rideron.impixoexports.com/api/v1',
    'development' => 'https://devrideron.impixoexports.com/api/v1',
];

$env = isset($_GET['env']) ? strtolower(trim($_GET['env'])) : 'production';

if (!array_key_exists($env, $baseUrls)) {
    $env = 'production';
}

echo json_encode([
    'environment' => $env,
    'base_url' => $baseUrls[$env],
]);
