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
use Nipwaayoni\Tests\SnsHandler\Events\SnsConfirmationRequestAlphaReceived;
use Nipwaayoni\Tests\SnsHandler\Events\SnsConfirmationRequestBetaReceived;
use Nipwaayoni\Tests\SnsHandler\Events\SnsMessageAlphaReceived;
use Nipwaayoni\Tests\SnsHandler\Events\SnsMessageBetaReceived;
use Nipwaayoni\Tests\SnsHandler\Events\SnsMessageNotDispatchable;
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

        $this->broker = new SnsBroker($this->validator, $this->config);
    }

    public function testMakesSnsMessageFromHttpRequest(): void
    {
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson());

        $message = $this->broker->makeMessageFromHttpRequest($request);

        $this->assertEquals(SnsMessage::NOTIFICATION_TYPE, $message->type());
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

    public function testDispatchesDefaultConfirmationRequestEvent(): void
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

    public function testDispatchesMappedConfirmationRequestEvent(): void
    {
        $this->configValues['confirmation-events'] = [
            SnsConfirmationRequestAlphaReceived::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
            SnsConfirmationRequestBetaReceived::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
            SnsConfirmationRequestReceived::class => ['*'],
        ];

        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'Type' => SnsMessage::SUBSCRIBE_TYPE,
                'SubscribeURL' => 'https://aws.amazon.com/subscribe/123',
                'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:AlphaTopic'
            ]));

        $this->broker->handleRequest($request);

        Event::assertDispatched(SnsConfirmationRequestAlphaReceived::class);
        Event::assertNotDispatched(SnsConfirmationRequestReceived::class);
    }

    public function testDispatchFirstMappedConfirmationEvent(): void
    {
        $this->configValues['confirmation-events'] = [
            SnsConfirmationRequestAlphaReceived::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
            SnsConfirmationRequestBetaReceived::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
            SnsConfirmationRequestReceived::class => ['*'],
        ];

        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'Type' => SnsMessage::SUBSCRIBE_TYPE,
                'SubscribeURL' => 'https://aws.amazon.com/subscribe/123',
                'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:AlphaTopic'
            ]));

        $this->broker->handleRequest($request);

        Event::assertDispatched(SnsConfirmationRequestAlphaReceived::class);
        Event::assertNotDispatched(SnsConfirmationRequestBetaReceived::class);
        Event::assertNotDispatched(SnsConfirmationRequestReceived::class);
    }

    public function testRejectsWithUnhandledTopicArnOnConfirmation(): void
    {
        $this->configValues['confirmation-events'] = [
            SnsConfirmationRequestBetaReceived::class => ['arn:aws:sns:us-west-2:123456789012:BetaTopic'],
        ];

        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'Type' => SnsMessage::SUBSCRIBE_TYPE,
                'SubscribeURL' => 'https://aws.amazon.com/subscribe/123',
                'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:AlphaTopic'
            ]));

        $this->expectException(SnsUnknownTopicArnException::class);
        $this->expectExceptionMessage('Unmappable TopicArn: arn:aws:sns:us-west-2:123456789012:AlphaTopic');

        $this->broker->handleRequest($request);

        Event::assertNotDispatched(SnsConfirmationRequestReceived::class);
    }

    public function testDispatchesDefaultNotificationEvent(): void
    {
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'MessageId' => 'abc123',
            ]));

        $this->broker->handleRequest($request);

        Event::assertDispatched(SnsMessageReceived::class);
    }

    public function testDispatchMappedNotificationEvent(): void
    {
        $this->configValues['message-events'] = [
            SnsMessageAlphaReceived::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
            SnsMessageBetaReceived::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
            SnsMessageReceived::class => ['*'],
        ];

        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'MessageId' => 'abc123',
                'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:AlphaTopic'
            ]));

        $this->broker->handleRequest($request);

        Event::assertDispatched(SnsMessageAlphaReceived::class);
        Event::assertNotDispatched(SnsMessageReceived::class);
    }

    public function testDispatchFirstMappedNotificationEvent(): void
    {
        $this->configValues['message-events'] = [
            SnsMessageAlphaReceived::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
            SnsMessageBetaReceived::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
            SnsMessageReceived::class => ['*'],
        ];

        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'MessageId' => 'abc123',
                'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:AlphaTopic'
            ]));

        $this->broker->handleRequest($request);

        Event::assertDispatched(SnsMessageAlphaReceived::class);
        Event::assertNotDispatched(SnsMessageBetaReceived::class);
        Event::assertNotDispatched(SnsMessageReceived::class);
    }

    public function testRejectsWithUnhandledTopicArnOnMessage(): void
    {
        $this->configValues['message-events'] = [
            SnsMessageBetaReceived::class => ['arn:aws:sns:us-west-2:123456789012:BetaTopic'],
        ];

        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'MessageId' => 'abc123',
                'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:AlphaTopic'
            ]));

        $this->expectException(SnsUnknownTopicArnException::class);
        $this->expectExceptionMessage('Unmappable TopicArn: arn:aws:sns:us-west-2:123456789012:AlphaTopic');

        $this->broker->handleRequest($request);

        Event::assertNotDispatched(SnsMessageReceived::class);
    }

    public function testThrowsExceptionForNonDispatchableMappedClass(): void
    {
        $this->configValues['message-events'] = [
            SnsMessageNotDispatchable::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
        ];

        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'MessageId' => 'abc123',
                'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:AlphaTopic'
            ]));

        $this->expectException(SnsException::class);
        $this->expectExceptionMessage('Mapped class is not dispatchable:');

        $this->broker->handleRequest($request);

        Event::assertNotDispatched(SnsMessageReceived::class);
    }

}
