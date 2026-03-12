<?php

return [
    'late_fee_amount' => (float) env('FEES_LATE_FEE_AMOUNT', 200),
    'grace_days' => (int) env('FEES_GRACE_DAYS', 3),
];

