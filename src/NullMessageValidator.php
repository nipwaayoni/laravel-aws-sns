<?php


namespace Nipwaayoni\SnsHandler;

use Aws\Sns\Message;

/**
 * Class NullMessageValidator
 * @package Nipwaayoni\SnsHandler
 *
 * @codeCoverageIgnore
 *
 * Used during testing.
 */
class NullMessageValidator extends \Aws\Sns\MessageValidator
{
    /** @var callable|null */
    private static $callback;

    public static function validateCallback(callable $callback = null): void
    {
        self::$callback = $callback;
    }

    public function validate(Message $message)
    {
        if (empty(self::$callback)) {
            return;
        }

        $callback = self::$callback;
        $callback($message);
    }
}
