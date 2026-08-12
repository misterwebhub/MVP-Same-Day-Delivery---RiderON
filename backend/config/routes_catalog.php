<?php

return [

    /*
     * "Popular routes" are ranked by completed-order volume within a trailing
     * window, not hard-coded — falls back to active routes ordered by id when
     * no order history exists yet (e.g. a fresh deployment).
     */
    'popular_routes_window_days' => (int) env('POPULAR_ROUTES_WINDOW_DAYS', 90),

    'popular_routes_limit' => (int) env('POPULAR_ROUTES_LIMIT', 10),

];
