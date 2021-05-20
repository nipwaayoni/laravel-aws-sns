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
                $className = $this->getSubscriptionEvent($message->topicArn());
                $className::dispatch($message);
                return;

        }

        throw new SnsException(sprintf('Unknown message type: %s', $message->type()));
    }

    private function getSubscriptionEvent(string $arn)
    {
        $map = [SnsConfirmationRequestReceived::class => ['*']];
        return $this->arnMap($arn, $map);
    }

    private function arnMap(string $arn, array $map)
    {
        $default = null;
        foreach($map as $className => $arnList) {
            if ($arnList[0] === '*') {
                $default = $className;
            }
            if (in_array($arn, $arnList)) {
                return $className;
            }
        }

        return $default;

    }
}
