<?php

return [
    'auto-confirm-subscriptions' => env('AUTO_CONFIRM_SUBSCRIPTIONS', true),
    'validate-sns-messages' => env('VALIDATE_SNS_MESSAGES', true),
    'confirmation-events' => [
        Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived::class => ['*']
    ],
    'message-events' => [
        Nipwaayoni\SnsHandler\Events\SnsMessageReceived::class => ['*']
    ],
];