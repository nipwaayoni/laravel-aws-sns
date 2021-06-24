#Introduction
This package provides an easy way of adding AWS SNS message handling to your Laravel application as a REST endpoint. The package can automatically confirm subscription requests and dispatches events when a message is received.

##How to create an SNS topic
[Instructions for creating an SNS topic in AWS can be found here](https://docs.aws.amazon.com/sns/latest/dg/sns-create-topic.html)

##How to add SNS Handler to your Application
**Note: This package currently requires Laravel >= 7.0.**

Use composer to require the package:

```bash
composer require nipwaayoni/laravel-aws-sns
```

##Handling events
Please review the [Laravel documentation on Events.](https://laravel.com/docs/8.x/events) You will need to write a listener to handle the events associated with the ARNs you register. Your listener class must handle the `SnsMessageReceived` event type, which is provided with this package. It should then be registered in the `EventServiceProvider` as mentioned in the Events documentation:
```php
use Nipwaayoni\SnsHandler\Events\SnsMessageReceived;
use App\Listeners\MySnsMessageHandler;

/**
* The event listener mappings for the application.
*
* @var array
  */
  protected $listen = [
  SnsMessageReceived::class => [
  MySnsMessageHandler::class,
  ],
  ];
  In your listener, the handle method will receive an SnsMessageReceived object which can be used to access the SnsMessage.

public function handle(SnsMessageReceived $event)
{
$message = $event->message();

       // do stuff with message
       $content = $message->content();
}
```
The message content will always be a string. You are responsible for any deserialization or other steps required to interpret the message content.

##Write a feature test to test the expected SNS feature
The ReceivesSnsMessagesTrait facilitates testing. Use the trait in your test

Sample test:
```php
public function testSnsRequestMapping(): void
{
Bus::fake();
$data = "Bob";
$this->sendSnsMessage($data);

        Bus::assertDispatched(SayHelloJob::class);

    }
```
Disable message signature validation when sending events from sources other than SNS (and during feature tests). Add to .env:
```
VALIDATE_SNS_MESSAGES=false
```

##How do I send SNS messages to my app?
The SNS message route responds to POST requests and expects content consistent with an SNS message from AWS.

The message content of an SNS message must be provided as a string value. If your payload is something other than a simple string (e.g. an array or some other sort of object), you will need to serialize your data before sending it using something like json_encode().

###POSTing directly to your app
[Amazon has an example of a POST request available here.](https://docs.aws.amazon.com/sns/latest/dg/sns-http-https-endpoint-as-subscriber.html)
You can add your serialized message in the message field. Note that manually POSTed messages cannot be validated (See above to disable message validation). We recommend only attempting this during testing, never in your production environment.


##How to subscribe your endpoint in AWS
[Follow these instructions to subscribe your endpoint using HTTPS](https://docs.aws.amazon.com/sns/latest/dg/sns-http-https-endpoint-as-subscriber.html)

**Note: You will only be able to subscribe your endpoint if it can be reached from the AWS SNS service**

