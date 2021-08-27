<?php


namespace Nipwaayoni\Tests\SnsHandler;

use Illuminate\Config\Repository;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\Events\SnsMessageReceived;
use PHPUnit\Framework\MockObject\MockObject;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /** @var Repository|mixed|MockObject  */
    protected $config;

    protected $configValues = [
        'validate-sns-messages' => true,
        'confirmation-events' => [
            SnsConfirmationRequestReceived::class => ['*']
        ],
        'message-events' => [
            SnsMessageReceived::class => ['*']
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(Repository::class);
        $this->config->method('get')
            ->willReturnCallback(function (string $key) {
                $parts = explode('.', $key);
                return $this->configValues[$parts[1]] ?? null;
            });
    }
}
