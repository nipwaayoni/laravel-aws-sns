<?php


namespace Nipwaayoni\SnsHandler;

use Aws\Sns\Message;

class SnsMessage
{
    public const NOTIFICATION_TYPE = 'Notification';
    public const SUBSCRIBE_TYPE = 'SubscriptionConfirmation';

    /**
     * @var Message
     */
    private $message;

    /**
     * SnsMessage constructor.
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function id(): string
    {
        return $this->message['MessageId'];
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->message['Type'];
    }

    /**
     * @return string
     */
    public function topicArn(): string
    {
        return $this->message['TopicArn'];
    }

    /**
     * @return string
     * @throws SnsException
     */
    public function subscribeUrl(): string
    {
        if ($this->message['Type'] !== self::SUBSCRIBE_TYPE) {
            throw new SnsException('Cannot get SubscribeURL from non-subscribeConfirmation message');
        }

        return $this->message['SubscribeURL'];
    }

    public function content(): string
    {
        return $this->message['Message'];
    }
}
