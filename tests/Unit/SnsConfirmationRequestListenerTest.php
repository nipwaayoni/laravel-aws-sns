<?php


namespace Nipwaayoni\Tests\SnsHandler\Unit;

use Aws\Sns\Message;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\Listeners\SnsConfirmationRequestListener;
use Nipwaayoni\SnsHandler\SnsConfirmSubscriptionException;
use Nipwaayoni\SnsHandler\SnsMessage;
use Nipwaayoni\Tests\SnsHandler\MakesSnsTests;
use Nipwaayoni\Tests\SnsHandler\SnsHttpTestHelperTrait;

class SnsConfirmationRequestListenerTest extends \Nipwaayoni\Tests\SnsHandler\TestCase
{
    use MakesSnsTests;
    use SnsHttpTestHelperTrait;

    /** @var SnsConfirmationRequestListener  */
    private $listener;


    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->listener = new SnsConfirmationRequestListener();
    }

    public function testThrowsExceptionIfConfirmSubscriptionFails(): void
    {
        $message = Message::fromJsonString($this->makeSnsMessageJson([
            'Type' => SnsMessage::SUBSCRIBE_TYPE,
            'SubscribeURL' => 'https://aws.amazon.com/subscribe/123',
        ]));

        $this->httpExpects([
            'https://aws.amazon.com/subscribe/123' => Http::response([], 404, [])
        ]);

        $event = new SnsConfirmationRequestReceived(new SnsMessage($message));

        $this->expectException(SnsConfirmSubscriptionException::class);
        $this->expectExceptionMessage('Subscription confirmation for arn:aws:sns:us-west-2:123456789012:MyTopic failed with status 404');
        Log::shouldReceive('error')->once();

        $this->listener->handle($event);
    }

    public function testConfirmsSubscriptionUsingSubscribeUrl(): void
    {
        $this->httpExpects(['https://aws.amazon.com/subscribe/123' => Http::response([], 200, [])]);

        $message = Message::fromJsonString($this->makeSnsMessageJson([
            'Type' => SnsMessage::SUBSCRIBE_TYPE,
            'SubscribeURL' => 'https://aws.amazon.com/subscribe/123',
        ]));

        $event = new SnsConfirmationRequestReceived(new SnsMessage($message));

        Log::shouldReceive('info')->once();

        $this->listener->handle($event);

        $this->httpAssertSent(function (Request $request) {
            return $request->url() === 'https://aws.amazon.com/subscribe/123';
        });
    }
}
