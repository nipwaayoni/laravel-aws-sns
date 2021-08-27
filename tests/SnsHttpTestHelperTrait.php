<?php


namespace Nipwaayoni\Tests\SnsHandler;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;

trait SnsHttpTestHelperTrait
{
    /** @var HttpClient  */
    private $client;

    /** @var HttpTransactionContainer */
    private $container;

    public function httpExpects(Response ...$responses): void
    {
        $this->container = new HttpTransactionContainer();

        $history = Middleware::history($this->container);

        $mock = new MockHandler($responses);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new \GuzzleHttp\Client(['handler' => $handlerStack]);
        $this->client = new \Http\Adapter\Guzzle6\Client($client);
    }

    public function httpAssertSent(callable $function): void
    {
        $this->assertCount(1, $this->container);

        $request = $this->container[0]->request();

        $function($request);
    }
}
