<?php


namespace Tests\MiamiOH\SnsHandler\Unit;

use MiamiOH\SnsHandler\SnsMessage;
use MiamiOH\SnsHandler\SnsMessageHandler;

class SnsMessageHandlerStub implements SnsMessageHandler
{
    /** @var callable */
    private static $callback;

    public static function handleCallback(callable $callback): void
    {
        self::$callback = $callback;
    }

    public function handle(SnsMessage $message): void
    {
        if (empty(self::$callback)) {
            return;
        }

        $callback = self::$callback;
        $callback($message);
    }
}
