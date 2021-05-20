<?php

namespace Nipwaayoni\Tests\SnsHandler\Unit;

use Aws\Sns\MessageValidator;
use Illuminate\Support\Facades\Event;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\Events\SnsMessageReceived;
use Nipwaayoni\SnsHandler\SnsBroker;
use Nipwaayoni\SnsHandler\SnsException;
use Nipwaayoni\SnsHandler\SnsHttpRequest;
use Nipwaayoni\SnsHandler\SnsMessage;
use Nipwaayoni\SnsHandler\SnsUnknownTopicArnException;
use PHPUnit\Framework\MockObject\MockObject;
use Nipwaayoni\Tests\SnsHandler\MakesSnsTests;
use Nipwaayoni\Tests\SnsHandler\TestCase;


class SnsBrokerTest extends TestCase
{
    use MakesSnsTests;

    /** @var SnsBroker  */
    private $broker;

    /** @var MessageValidator|MockObject  */
    private $validator;


    public function setUp(): void
    {
        parent::setUp();

        Event::fake();


        $this->validator = $this->createMock(MessageValidator::class);

        $this->broker = new SnsBroker($this->validator);
    }

    public function testMakesSnsMessageFromHttpRequest(): void
    {
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson());

        $message = $this->broker->makeMessageFromHttpRequest($request);

        $this->assertEquals(SnsMessage::NOTIFICATION_TYPE, $message->type());
    }

    public function testRejectsMessageWithUnknownTopicArn(): void
    {
        $this->markTestSkipped("Doesn't work with event faking and we think this test will go away after refactoring.");
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson(['TopicArn' => 'arn:aws:sns:us-west-2:123456789012:Unknown']));

        $this->expectException(SnsUnknownTopicArnException::class);
        $this->expectExceptionMessage('No handler registered for TopicArn arn:aws:sns:us-west-2:123456789012:Unknown');

        $this->broker->handleRequest($request);
    }

    public function testThrowsExceptionForUnknownMessageType(): void
    {
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson(['Type' => 'Unknown']));

        $this->expectException(SnsException::class);
        $this->expectExceptionMessage('Unknown message type: Unknown');

        $this->broker->handleRequest($request);
        Event::assertNotDispatched(SnsMessageReceived::class);
    }

    public function testDispatchesSnsConfirmationRequestEvent(): void
    {
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'Type' => SnsMessage::SUBSCRIBE_TYPE,
                'SubscribeURL' => 'https://aws.amazon.com/subscribe/123',
            ]));

        $this->broker->handleRequest($request);
        Event::assertDispatched(SnsConfirmationRequestReceived::class);
    }

    public function testDispatchesDefaultNotificationMessage(): void
    {

        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'MessageId' => 'abc123',
            ]));

        $this->broker->handleRequest($request);
        Event::assertDispatched(SnsMessageReceived::class);
    }

    public function testValidatesSnsMessage(): void
    {
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'MessageId' => 'abc123',
            ]));

        $this->validator->expects($this->once())->method('validate');

        $this->broker->handleRequest($request);
    }
}
