<?php

return [

    /*
     * Fallback default for the `order.auto_complete_minutes` app_settings key
     * (docs/04-state-machine.md: DELIVERED -> COMPLETED). 0 means COMPLETED
     * fires immediately after a successful delivery OTP verification; a
     * positive value defers auto-completion to a scheduled job (not yet
     * built) so disputes can be raised within the grace window first.
     */
    'auto_complete_minutes' => (int) env('ORDER_AUTO_COMPLETE_MINUTES', 0),

];
