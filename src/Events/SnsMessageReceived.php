<?php


namespace Nipwaayoni\SnsHandler\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Nipwaayoni\SnsHandler\SnsMessage;

class SnsMessageReceived
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
