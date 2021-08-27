<?php


namespace Nipwaayoni\SnsHandler\Listeners;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Illuminate\Support\Facades\Log;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\SnsConfirmSubscriptionException;
use Nipwaayoni\SnsHandler\SnsException;
use Nipwaayoni\SnsHandler\SnsMessage;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class SnsConfirmationRequestListener
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @param ClientInterface|null $client
     * @param RequestFactoryInterface|null $requestFactory
     */
    public function __construct(
        ClientInterface $client = null,
        RequestFactoryInterface $requestFactory = null
    ) {
        $this->client = $client ?? HttpClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
    }

    public function handle(SnsConfirmationRequestReceived $event)
    {
        $message = $event->message();

        $response = $this->getResponse($message);

        Log::info(sprintf('Subscription confirmation for %s succeeded with status %s', $message->topicArn(), $response->getStatusCode()));
    }

    /**
     * @param SnsMessage $message
     * @return ResponseInterface
     * @throws SnsConfirmSubscriptionException
     * @throws SnsException
     */
    private function getResponse(SnsMessage $message): ResponseInterface
    {
        try {
            return $this->client->sendRequest(
                $this->requestFactory->createRequest('GET', $message->subscribeUrl())
            );
        } catch (ClientExceptionInterface $e) {
            throw new SnsConfirmSubscriptionException(
                sprintf('Subscription confirmation for %s failed with status %s', $message->topicArn(), $e->getCode())
            );
        }
    }
}
