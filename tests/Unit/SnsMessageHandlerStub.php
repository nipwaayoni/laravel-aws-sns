<?php


namespace Nipwaayoni\Tests\SnsHandler\Unit;

use Nipwaayoni\SnsHandler\SnsMessage;
use Nipwaayoni\SnsHandler\SnsMessageHandler;

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
