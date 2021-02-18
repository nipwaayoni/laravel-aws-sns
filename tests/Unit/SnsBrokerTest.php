<?php

namespace Tests\MiamiOH\SnsHandler\Unit;

use Aws\Sns\MessageValidator;
use MiamiOH\SnsHandler\SnsBroker;
use MiamiOH\SnsHandler\SnsConfirmSubscriptionException;
use MiamiOH\SnsHandler\SnsException;
use MiamiOH\SnsHandler\SnsHttpRequest;
use MiamiOH\SnsHandler\SnsMessage;
use MiamiOH\SnsHandler\SnsTopicMapper;
use MiamiOH\SnsHandler\SnsUnknownTopicArnException;
use MiamiOH\IamPortal\Util\HttpClient;
use MiamiOH\IamPortal\Util\HttpResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\MiamiOH\SnsHandler\MakesSnsTests;
use Tests\MiamiOH\SnsHandler\TestCase;
use Illuminate\Support\Facades\Http;



class SnsBrokerTest extends TestCase
{
    use MakesSnsTests;

    /** @var SnsBroker  */
    private $broker;

    /** @var SnsTopicMapper  */
    private $topicMapper;
    /** @var MessageValidator|MockObject  */
    private $validator;
    /** @var HttpClient|MockObject  */
    private $httpClient;

    public function setUp(): void
    {
        parent::setUp();

        $this->topicMapper = new SnsTopicMapper();
        $this->topicMapper->map(
            'arn:aws:sns:us-west-2:123456789012:MyTopic',
            SnsMessageHandlerStub::class
        );

        $this->validator = $this->createMock(MessageValidator::class);
//        $this->httpClient = $this->createMock(HttpClient::class);

        $this->broker = new SnsBroker($this->topicMapper, $this->validator);
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
    }

    public function testConfirmsSubscriptionUsingSubscribeUrl(): void
    {
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'Type' => SnsMessage::SUBSCRIBE_TYPE,
                'SubscribeURL' => 'https://aws.amazon.com/subscribe/123',
            ]));
        Http::fake();
//        Http::fake(['https://aws.amazon.com/subscribe/123' => Http::response([], 200)]);
//        $this->httpClient->expects($this->once())->method('get')
//            ->with($this->equalTo('https://aws.amazon.com/subscribe/123'))
//            ->willReturn($this->makeHttpResponseMock());

        $this->broker->handleRequest($request);
    }

    public function testThrowsExceptionIfConfirmSubscriptionFails(): void
    {
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'Type' => SnsMessage::SUBSCRIBE_TYPE,
                'SubscribeURL' => 'https://aws.amazon.com/subscribe/123',
            ]));

        $response = $this->makeHttpResponseMock(404);

        $this->httpClient->expects($this->once())->method('get')
            ->with($this->equalTo('https://aws.amazon.com/subscribe/123'))
            ->willReturn($response);

        $this->expectException(SnsConfirmSubscriptionException::class);
        $this->expectExceptionMessage('Subscription confirmation for arn:aws:sns:us-west-2:123456789012:MyTopic failed with status 404');

        $this->broker->handleRequest($request);
    }

    public function testCallsHandlerWithNotificationMessage(): void
    {
        $request = $this->createMock(SnsHttpRequest::class);
        $request->expects($this->once())->method('jsonContent')
            ->willReturn($this->makeSnsMessageJson([
                'MessageId' => 'abc123',
            ]));

        SnsMessageHandlerStub::handleCallback(function (SnsMessage $message) {
            $this->assertEquals('abc123', $message->id());
        });

        $this->broker->handleRequest($request);
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

    private function makeHttpResponseMock(int $status = 200): HttpResponse
    {
        $response = $this->createMock(HttpResponse::class);
        $response->method('status')->willReturn($status);
        $response->method('successful')->willReturn($status >= 200 && $status < 400);

        return $response;
    }
}
