<?php


namespace Nipwaayoni\SnsHandler\Testing;

use Illuminate\Testing\TestResponse;

/**
 * Trait ReceivesSnsMessages
 * @package Nipwaayoni\SnsHandler\Testing
 *
 * @codeCoverageIgnore
 * Just a test helper
 */
trait ReceivesSnsMessages
{
    public function sendSnsMessage(string $data, string $arn = "default"): TestResponse
    {
        $headers = [
            'x-amz-sns-message-type' => 'Notification',
            'x-amz-sns-message-id' => '22b80b92-fdea-4c2c-8f9d-bdfb0c7bf324',
            'x-amz-sns-topic-arn' => $arn,
            'x-amz-sns-subscription-arn' => 'arn:aws:sns:us-west-2:123456789012:EntityRequest:c9135db0-26c4-47ec-8998-413945fb5a96',
            'Content-Length' => '773',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Host' => 'example.com',
            'Connection' => 'Keep-Alive',
            'User-Agent' => 'Amazon Simple Notification Service Agent',
        ];
        $body = [
                'Type' => 'Notification',
                'MessageId' => '22b80b92-fdea-4c2c-8f9d-bdfb0c7bf324',
                'TopicArn' => $arn,
                'Subject' => 'My First Message',
                'Message' => $data,
                'Timestamp' => '2012-05-02T00:54:06.655Z',
                'SignatureVersion' => '1',
                'Signature' => 'EXAMPLEw6JRN...',
                'SigningCertURL' => 'https://sns.us-west-2.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem',
                'UnsubscribeURL' => 'https://sns.us-west-2.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-west-2:123456789012:MyTopic:c9135db0-26c4-47ec-8998-413945fb5a9',
        ];

        return $this->postJson('/api/sns/message', $body, $headers);
    }
}
