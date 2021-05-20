<?php


namespace Nipwaayoni\SnsHandler;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Support\Facades\Log;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\Events\SnsMessageReceived;

class SnsBroker
{
    /**
     * @var MessageValidator
     */
    private $validator;
    /**
     * @var Log
     */
    private $log;

    public function __construct(MessageValidator $validator)
    {
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
                $className = $this->getNotificationEvent($message->topicArn());
                $className::dispatch($message);
                Log::debug(sprintf('Dispatched SNS message from %s with %s', $message->topicArn(), $className));
                return;

            case SnsMessage::SUBSCRIBE_TYPE:
                $className = $this->getSubscriptionEvent($message->topicArn());
                $className::dispatch($message);
                Log::info(sprintf('Dispatched confirmation event for subscription to topic %s with %s', $message->topicArn(), $className));
                return;

        }

        throw new SnsException(sprintf('Unknown message type: %s', $message->type()));
    }

    private function getSubscriptionEvent(string $arn)
    {
        $map = [SnsConfirmationRequestReceived::class => ['*']];
        return $this->arnMap($arn, $map);
    }

    private function getNotificationEvent(string $arn)
    {
        $map = [SnsMessageReceived::class => ['*']];
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
