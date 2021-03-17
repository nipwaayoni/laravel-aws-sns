<?php


namespace Tests\MiamiOH\SnsHandler\Feature;

use Aws\Sns\MessageValidator;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use MiamiOH\SnsHandler\NullMessageValidator;
use MiamiOH\SnsHandler\ServiceProvider;
use MiamiOH\SnsHandler\SnsMessage;
use Tests\MiamiOH\SnsHandler\MakesSnsTests;
use Tests\MiamiOH\SnsHandler\Unit\SnsMessageHandlerStub;

class SnsHandlerTest extends \Tests\MiamiOH\SnsHandler\TestCase
{
    use MakesSnsTests;

    public function setUp(): void
    {
        parent::setUp();
        $this->app->bind(MessageValidator::class, NullMessageValidator::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('sns-handler.sns-class-map', [
            'arn:aws:sns:us-west-2:123456789012:MyTopic' => SnsMessageHandlerStub::class,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }

    public function testReturnsNotFoundForUnknownTopicArn(): void
    {
        $data = $this->makeSnsMessageData([
            'Type' => SnsMessage::NOTIFICATION_TYPE,
            'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:Unknown',
        ]);
        $response = $this->postJson('/api/sns/message', $data);

        $this->assertEquals(404, $response->status());
    }

    public function testConfirmsSubscriptionForKnownTopicArn(): void
    {
        Http::fake();

        $data = $this->makeSnsMessageData([
            'Type' => SnsMessage::SUBSCRIBE_TYPE,
            'Message' => json_encode(['id' => 123, 'color' => 'red'], true),
            'SubscribeURL' => 'https://aws.amazon.com/sns/register/abc123'
        ]);

        $this->postJson('/api/sns/message', $data);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://aws.amazon.com/sns/register/abc123';
        });
    }

    public function testRespondsWithOkAfterConfirmsSubscription(): void
    {
        Http::fake();

        $data = $this->makeSnsMessageData([
            'Type' => SnsMessage::SUBSCRIBE_TYPE,
            'Message' => json_encode(['id' => 123, 'color' => 'red'], true),
            'SubscribeURL' => 'https://aws.amazon.com/sns/register/abc123'
        ]);

        $response = $this->postJson('/api/sns/message', $data);

        $this->assertEquals(200, $response->status());
    }

    public function testReturnsBadGatewayResponseIfConfirmationFails(): void
    {
        Http::fake([
            '*' => Http::response(null, 404),
        ]);

        $data = $this->makeSnsMessageData([
            'Type' => SnsMessage::SUBSCRIBE_TYPE,
            'Message' => json_encode(['id' => 123, 'color' => 'red'], true),
            'SubscribeURL' => 'https://aws.amazon.com/sns/register/abc123'
        ]);

        $response = $this->postJson('/api/sns/message', $data);

        $this->assertEquals(502, $response->status());
    }

    public function testSendsMessageToRegisteredHandler(): void
    {
        $data = $this->makeSnsMessageData([
            'Type' => SnsMessage::NOTIFICATION_TYPE,
            'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:MyTopic',
            'Message' => 'Test message',
        ]);

        SnsMessageHandlerStub::handleCallback(function (SnsMessage $message) {
            $this->assertEquals('Test message', $message->message());
        });

        $response = $this->postJson('/api/sns/message', $data);

        $this->assertEquals(200, $response->status());
    }
}
