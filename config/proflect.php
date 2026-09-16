<?php

return [
    'offer_duration_days' => (int) env('PROFLECT_OFFER_DURATION_DAYS', 30),
    'offer_reminder_day' => (int) env('PROFLECT_OFFER_REMINDER_DAY', 14),
    'term_notice_days' => (int) env('PROFLECT_TERM_NOTICE_DAYS', 30),
];
