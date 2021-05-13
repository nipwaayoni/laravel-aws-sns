<?php


namespace Nipwaayoni\SnsHandler\Listeners;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\SnsConfirmSubscriptionException;

class SnsConfirmationRequestListener
{

    public function handle(SnsConfirmationRequestReceived $event)
    {
        $message = $event->message();
        $response = Http::get($message->subscribeUrl());
        if ($response->successful()) {
           return;
        }
        $error = sprintf('Subscription confirmation for %s failed with status %s', $message->topicArn(), $response->status());
        Log::error($error);
        throw new SnsConfirmSubscriptionException($error);
    }

}