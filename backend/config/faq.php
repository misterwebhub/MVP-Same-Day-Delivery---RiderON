<?php

return [

    /*
     * Static help content for GET /support/faq. No admin UI backs this yet
     * (docs/09 excludes in-app chat / advanced support tooling from Phase 1),
     * so it lives here rather than in a database table.
     */
    'items' => [
        [
            'question' => 'How do I track my parcel?',
            'answer' => 'Open the order from your Orders tab to see its live status and estimated timings.',
            'category' => 'tracking',
        ],
        [
            'question' => 'What items are prohibited from shipping?',
            'answer' => 'Check the Prohibited Items list shown during booking. It is periodically updated, so please review it each time you book.',
            'category' => 'booking',
        ],
        [
            'question' => 'How is the delivery price calculated?',
            'answer' => 'Pricing depends on the route, parcel weight slab, and any applicable surcharges. You can always preview the full breakdown before paying.',
            'category' => 'payment',
        ],
        [
            'question' => 'What happens if my parcel is damaged or lost?',
            'answer' => 'Raise a support ticket with the "Damaged Parcel" category and your order ID. Our support team will investigate and, if applicable, process a refund.',
            'category' => 'delivery',
        ],
        [
            'question' => 'How do I cancel an order?',
            'answer' => 'You can cancel from the order detail screen. Refund eligibility depends on how far the order has progressed.',
            'category' => 'cancellation',
        ],
        [
            'question' => 'The receiver was unavailable at delivery. What now?',
            'answer' => 'Raise a support ticket with the "Receiver Unavailable" category. The rider will wait until the configured deadline before escalating.',
            'category' => 'delivery',
        ],
    ],

];
