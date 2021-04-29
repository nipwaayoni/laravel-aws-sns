<?php


namespace Nipwaayoni\SnsHandler;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\Events\SnsMessageReceived;

class SnsBroker
{
    /**
     * @var SnsTopicMapper
     */
    private $topicMapper;
    /**
     * @var MessageValidator
     */
    private $validator;
    /**
     * @var Log
     */
    private $log;

    public function __construct(SnsTopicMapper $topicMapper, MessageValidator $validator)
    {
        $this->topicMapper = $topicMapper;
        $this->validator = $validator;
    }

    /**
     * @param SnsHttpRequest $request
     * @return SnsMessage
     */
    public function makeMessageFromHttpRequest(SnsHttpRequest $request): SnsMessage
    {
        $message = Message::fromJsonString($request->jsonContent());

        $this->validator->validate($message);

        return new SnsMessage($message);
    }

    /**
     * @param SnsHttpRequest $request
     * @throws SnsConfirmSubscriptionException
     * @throws SnsUnknownTopicArnException
     * @throws SnsException
     */
    public function handleRequest(SnsHttpRequest $request): void
    {
        $message = $this->makeMessageFromHttpRequest($request);

        switch ($message->type()) {
            case SnsMessage::NOTIFICATION_TYPE:
                SnsMessageReceived::dispatch($message);
                $handler = $this->topicMapper->getHandlerForTopic($message->topicArn());
                Log::debug(sprintf('Handling SNS message from %s with %s', $message->topicArn(), get_class($handler)));
                $handler->handle($message);
                return;

            case SnsMessage::SUBSCRIBE_TYPE:
                Log::info(sprintf('Confirming subscription to topic %s', $message->topicArn()));
                SnsConfirmationRequestReceived::dispatch($message);
                //TODO Make this work with Laravel 6, as the Http facade was introduced in Laravel 7
                $response = Http::get($message->subscribeUrl());
                if ($response->successful()) {
                    return;
                }
                $error = sprintf('Subscription confirmation for %s failed with status %s', $message->topicArn(), $response->status());
                Log::error($error);
                throw new SnsConfirmSubscriptionException($error);
        }

        throw new SnsException(sprintf('Unknown message type: %s', $message->type()));
    }
}
