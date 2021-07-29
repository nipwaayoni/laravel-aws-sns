<?php


namespace Nipwaayoni\SnsHandler\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\SnsConfirmSubscriptionException;
use Nipwaayoni\SnsHandler\SnsException;

class SnsConfirmationRequestListener
{
    public function handle(SnsConfirmationRequestReceived $event)
    {
        $message = $event->message();
        //TODO Make this work with Laravel 6, as the Http facade was introduced in Laravel 7
        $response = $this->getResponse($message);
        if ($response->successful()) {
            $info = sprintf('Subscription confirmation for %s succeeded with status %s', $message->topicArn(), $response->status());
            Log::info($info);
            return;
        }
        $error = sprintf('Subscription confirmation for %s failed with status %s', $message->topicArn(), $response->status());
        Log::error($error);
        throw new SnsConfirmSubscriptionException($error);
    }

    /**
     * @param \Nipwaayoni\SnsHandler\SnsMessage $message
     * @return \Illuminate\Http\Client\Response
     * @throws \Nipwaayoni\SnsHandler\SnsException
     */
    private function getResponse(\Nipwaayoni\SnsHandler\SnsMessage $message): \Illuminate\Http\Client\Response
    {
        if (class_exists(Http::class)) {
            return Http::get($message->subscribeUrl());
        }
        throw new SnsException("Unable to determine HTTP method");
    }
}
