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
                return;

        }

        throw new SnsException(sprintf('Unknown message type: %s', $message->type()));
    }
}
