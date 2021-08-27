<?php


namespace Nipwaayoni\Tests\SnsHandler\Feature;

use Aws\Sns\MessageValidator;
use Illuminate\Support\Facades\Event;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\Events\SnsMessageReceived;
use Nipwaayoni\SnsHandler\NullMessageValidator;
use Nipwaayoni\SnsHandler\ServiceProvider;
use Nipwaayoni\SnsHandler\SnsMessage;
use Nipwaayoni\Tests\SnsHandler\Events\SnsConfirmationRequestAlphaReceived;
use Nipwaayoni\Tests\SnsHandler\Events\SnsConfirmationRequestBetaReceived;
use Nipwaayoni\Tests\SnsHandler\Events\SnsMessageAlphaReceived;
use Nipwaayoni\Tests\SnsHandler\Events\SnsMessageBetaReceived;
use Nipwaayoni\Tests\SnsHandler\MakesSnsTests;

class SnsHandlerDefaultEventsTest extends \Nipwaayoni\Tests\SnsHandler\TestCase
{
    use MakesSnsTests;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->app->bind(MessageValidator::class, NullMessageValidator::class);
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('sns-handler.confirmation-events', [
            SnsConfirmationRequestReceived::class => ['*'],
        ]);

        $app['config']->set('sns-handler.message-events', [
            SnsMessageReceived::class => ['*'],
        ]);
    }

    public function testDispatchesDefaultConfirmationEvent(): void
    {
        $data = $this->makeSnsMessageData([
            'Type' => SnsMessage::SUBSCRIBE_TYPE,
            'Message' => json_encode(['id' => 123, 'color' => 'red'], true),
            'SubscribeURL' => 'https://aws.amazon.com/sns/register/abc123'
        ]);

        $response = $this->postJson('/api/sns/message', $data);

        $this->assertEquals(200, $response->status());
        Event::assertDispatched(SnsConfirmationRequestReceived::class);
        Event::assertNotDispatched(SnsConfirmationRequestAlphaReceived::class);
        Event::assertNotDispatched(SnsConfirmationRequestBetaReceived::class);
    }

    public function testDispatchesDefaultMessageEvent(): void
    {
        $data = $this->makeSnsMessageData([
            'Type' => SnsMessage::NOTIFICATION_TYPE,
            'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:MyTopic',
            'Message' => 'Test message',
        ]);

        $response = $this->postJson('/api/sns/message', $data);

        $this->assertEquals(200, $response->status());
        Event::assertDispatched(SnsMessageReceived::class);
        Event::assertNotDispatched(SnsMessageAlphaReceived::class);
        Event::assertNotDispatched(SnsMessageBetaReceived::class);
    }
}
