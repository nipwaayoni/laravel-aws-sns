<?php


namespace Tests\MiamiOH\SnsHandler;

use Aws\Sns\Message;
use MiamiOH\SnsHandler\SnsMessage;

trait MakesSnsTests
{
    protected function makeSnsMessage(array $overrides = []): Message
    {
        return Message::fromJsonString($this->makeSnsMessageJson($overrides));
    }

    protected function makeSnsMessageJson(array $overrides = []): string
    {
        return json_encode($this->makeSnsMessageData($overrides), JSON_THROW_ON_ERROR);
    }

    protected function makeSnsMessageData(array $overrides = []): array
    {
        $data = array_merge([
            'Type' => 'Notification',
            'MessageId' => '22b80b92-fdea-4c2c-8f9d-bdfb0c7bf324',
            'TopicArn' => 'arn:aws:sns:us-west-2:123456789012:MyTopic',
            'Subject' => 'My First Message',
            'Message' => '{\n        \"shortName\": \"ProjectDragonfly@miamioh.edu\",\n        \"longName\": \"Archived messages from the closed ProjectDragonfly@Gmail.com account\",\n        \"firstName\": \"Archived\",\n        \"lastName\": \"messages from the closed ProjectDragonfly@Gmail.com account\",\n        \"department\": \"Biology\",\n        \"primaryTrusteeID\": \"LAWSONZM\",\n        \"secondaryTrusteeID\": \"CELSORMG\",\n        \"routing\": \"EntityShortName@miamioh.edu\",\n        \"createAD\": false,\n        \"directorySearchable\": \"No\",\n        \"ticketNumber\": 15918867,\n        \"comments\": \"Thank you very much!\"\n    }',
            'Timestamp' => '2012-05-02T00:54:06.655Z',
            'SignatureVersion' => 1,
            'Signature' => 'EXAMPLEw6JRN...',
            'SigningCertURL' => 'https://sns.us-west-2.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem',
            'UnsubscribeURL' => 'https://sns.us-west-2.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-west-2:123456789012:MyTopic:c9135db0-26c4-47ec-8998-413945fb5a9'
        ], $overrides);

        if ($data['Type'] === SnsMessage::SUBSCRIBE_TYPE) {
            unset($data['UnsubscribeURL']);
            if (empty($data['SubscribeURL'])) {
                $data['SubscribeURL'] = 'https://aws.amazon.com/subscribe/123';
            }
            if (empty($data['Token'])) {
                $data['Token'] = '2336412f37f...';
            }
        }

        return $data;
    }
}
