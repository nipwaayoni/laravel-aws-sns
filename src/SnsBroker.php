<?php


namespace Nipwaayoni\SnsHandler;

use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Facades\Log;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\Events\SnsMessageReceived;
use Nipwaayoni\Tests\SnsHandler\Events\SnsMessageAlphaReceived;
use Nipwaayoni\Tests\SnsHandler\Events\SnsMessageBetaReceived;

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
    /**
     * @var Config
     */
    private $config;

    public function __construct(MessageValidator $validator, Config $config)
    {
        $this->validator = $validator;
        $this->config = $config;
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

    /**
     * @param string $arn
     * @return string
     * @throws SnsUnknownTopicArnException
     */
    private function getSubscriptionEvent(string $arn): string
    {
        $map = [SnsConfirmationRequestReceived::class => ['*']];
        return $this->arnMap($arn, $map);
    }

    /**
     * @param string $arn
     * @return string
     * @throws SnsUnknownTopicArnException
     */
    private function getNotificationEvent(string $arn): string
    {
        $map = $this->config->get('sns-handler.message-events', []);
        return $this->arnMap($arn, $map);
    }

    /**
     * @param string $arn
     * @param array $map
     * @return string
     * @throws SnsUnknownTopicArnException
     */
    private function arnMap(string $arn, array $map): string
    {
        $default = null;
        foreach ($map as $className => $arnList) {
            if ($arnList[0] === '*') {
                $default = $className;
            }
            if (in_array($arn, $arnList)) {
                return $className;
            }
        }

        if (null === $default) {
            throw new SnsUnknownTopicArnException(sprintf('Unmappable TopicArn: %s', $arn));
        }

        // TODO ensure class is dispatchable

        return $default;
    }
}
