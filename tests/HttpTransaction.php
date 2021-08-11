<?php


namespace Nipwaayoni\Tests\SnsHandler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpTransaction
{
    /**
     * @var array
     */
    private $transaction;

    public function __construct(array $transaction)
    {
        $this->transaction = $transaction;
    }

    public function request(): RequestInterface
    {
        return $this->transaction['request'];
    }

    public function response(): ResponseInterface
    {
        return $this->transaction['response'];
    }
}
