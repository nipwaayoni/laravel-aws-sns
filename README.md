# Laravel SNS Handler

This package provides an easy way of adding AWS SNS message handling to your Laravel application as a REST endpoint. The package can automatically confirm subscription requests and dispatches events when a message is received.

## How to create an SNS topic

[Instructions for creating an SNS topic in AWS can be found here](https://docs.aws.amazon.com/sns/latest/dg/sns-create-topic.html)

## How to add SNS Handler to your Application

**Note: This package currently requires Laravel >= 8.0. Laravel 7 is no longer supported due to security concerns.**

Use composer to require the package:

```bash
composer require nipwaayoni/laravel-aws-sns
```

## Handling events

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
 ```

In your listener, the handle method will receive an SnsMessageReceived object which can be used to access the SnsMessage.

```php
public function handle(SnsMessageReceived $event)
{
   $message = $event->message();
   // do stuff with message
   $content = $message->content();
}
```

The message content will always be a string. You are responsible for any deserialization or other steps required to interpret the message content.

### Dispatching specific events for specific ARNs

It is possible to map individual event classes to individual ARNs. The map is stored in the sns-handler.php config file (ensure this is published if it does not exist in your project). You can map either subscription events or message events in the following format:

```php
 'message-events' => [
    SnsMessageAlphaReceived::class => ['arn:aws:sns:us-west-2:123456789012:AlphaTopic'],
    SnsMessageBetaReceived::class => ['arn:aws:sns:us-west-2:123456789012:BetaTopic', 'arn:aws:sns:us-west-2:123456789012:GammaTopic'],
    SnsMessageReceived::class => ['*'],
 ];
```

Note that the map is parsed in order, so the first match found in the list will be the class dispatched.

Note that a default event class is not required, but if a matching event cannot be found in the map, a 404 will be returned if a message is sent using that ARN.

## Write a feature test to test the expected SNS feature

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

## How do I send SNS messages to my app?

The SNS message route responds to POST requests and expects content consistent with an SNS message from AWS.

The message content of an SNS message must be provided as a string value. If your payload is something other than a simple string (e.g. an array or some other sort of object), you will need to serialize your data before sending it using something like json_encode().

### POSTing directly to your app

[Amazon has an example of a POST request available here.](https://docs.aws.amazon.com/sns/latest/dg/sns-http-https-endpoint-as-subscriber.html)
You can add your serialized message in the message field. Note that manually POSTed messages cannot be validated (See above to disable message validation). We recommend only attempting this during testing, never in your production environment.


## How to subscribe your endpoint in AWS

This package adds a route to your application for incoming SNS requests. Note that because this is an api route, any API middleware you have applied in your application will affect this route. The route will be `{Your application's base URL}/api/sns/message`.
[Follow these instructions to subscribe your endpoint using HTTPS](https://docs.aws.amazon.com/sns/latest/dg/sns-http-https-endpoint-as-subscriber.html).

This package is configured to automatically respond to all SNS subscription requests sent to its endpoint. This can be disabled by adding the following to your .env file:

```
AUTO_CONFIRM_SUBSCRIPTIONS=false
```

**Note: You will only be able to subscribe your endpoint if it can be reached from the AWS SNS service**

## Development

This package is expected to work with supported versions of Laravel, including LTS releases. During development, you should be sure to run tests and validate expected behaviors under different releases. Since we use the `orchestra/testbench` package, you can easily switch between installed Laravel framework releases using `composer`:

```bash
# Laravel 6
composer require --dev orchestra/testbench:^4.0 -W
# Laravel 8
composer require --dev orchestra/testbench:^6.0 -W
```

New releases of Laravel should be added to the GitHub workflow matrix in `.github/workflows/run-tests.yml`.
