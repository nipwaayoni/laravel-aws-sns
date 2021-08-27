<?php


namespace Nipwaayoni\Tests\SnsHandler\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Nipwaayoni\SnsHandler\SnsMessage;

class SnsConfirmationRequestBetaReceived
{
    use Dispatchable, InteractsWithSockets;

    private $message;

    public function __construct(SnsMessage $message)
    {
        $this->message = $message;
    }

    public function message(): SnsMessage
    {
        return $this->message;
    }
}
