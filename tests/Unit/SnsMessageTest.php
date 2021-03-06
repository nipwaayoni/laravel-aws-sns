<?php

namespace Nipwaayoni\Tests\SnsHandler\Unit;

use Nipwaayoni\SnsHandler\SnsException;
use Nipwaayoni\SnsHandler\SnsMessage;
use Nipwaayoni\Tests\SnsHandler\MakesSnsTests;
use Nipwaayoni\Tests\SnsHandler\TestCase;

class SnsMessageTest extends TestCase
{
    use MakesSnsTests;

    public function testReturnsIdFromMessage(): void
    {
        $message = new SnsMessage($this->makeSnsMessage(['MessageId' => 'abc123']));

        $this->assertEquals('abc123', $message->id());
    }

    public function testReturnsTypeFromMessage(): void
    {
        $message = new SnsMessage($this->makeSnsMessage(['Type' => SnsMessage::NOTIFICATION_TYPE]));

        $this->assertEquals(SnsMessage::NOTIFICATION_TYPE, $message->type());
    }

    public function testReturnsTopicArnFromMessage(): void
    {
        $message = new SnsMessage($this->makeSnsMessage(['TopicArn' => 'abc123']));

        $this->assertEquals('abc123', $message->topicArn());
    }

    public function testReturnsSubscribeUrlForSubscribeConfirmationMessage(): void
    {
        $message = new SnsMessage($this->makeSnsMessage([
            'Type' => SnsMessage::SUBSCRIBE_TYPE,
            'SubscribeURL' => 'https://aws.amazon.com/subscribe/123',
        ]));

        $this->assertEquals('https://aws.amazon.com/subscribe/123', $message->subscribeUrl());
    }

    public function testThrowsExceptionForSubscribeUrlForNonSubscribeConfirmationMessage(): void
    {
        $message = new SnsMessage($this->makeSnsMessage(['Type' => SnsMessage::NOTIFICATION_TYPE,]));

        $this->expectException(SnsException::class);
        $this->expectExceptionMessage('Cannot get SubscribeURL from non-subscribeConfirmation message');

        $this->assertEquals('https://aws.amazon.com/subscribe/123', $message->subscribeUrl());
    }

    public function testReturnsMessageFromMessage(): void
    {
        $json = json_encode(['id' => 123]);
        $message = new SnsMessage($this->makeSnsMessage(['Message' => $json]));

        $this->assertEquals($json, $message->content());
    }
}
