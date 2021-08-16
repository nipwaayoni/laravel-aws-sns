<?php

return [
    'validate-sns-messages' => env('VALIDATE_SNS_MESSAGES', true),
    'message-events' => [
        Nipwaayoni\SnsHandler\Events\SnsMessageReceived::class => ['*']
    ],
];