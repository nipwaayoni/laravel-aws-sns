<?php


namespace MiamiOH\SnsHandler;

use Throwable;

class SnsUnknownTopicArnException extends SnsException
{
    public function __construct(string $topicArn = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('No handler registered for TopicArn %s', $topicArn), $code, $previous);
    }
}
