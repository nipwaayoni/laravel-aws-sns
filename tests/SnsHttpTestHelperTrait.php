<?php


namespace Nipwaayoni\Tests\SnsHandler;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Nipwaayoni\SnsHandler\SnsException;

trait SnsHttpTestHelperTrait
{
    public function httpExpects(array $content = null): void
    {
        if (class_exists(Http::class)) {
            Http::fake($content);
            return;
        }
        throw new SnsException("Unable to determine HTTP method");
    }

    public function httpAssertSent(callable $function): void
    {
        if (class_exists(Http::class)) {
            Http::assertSent($function);
            return;
        }
        throw new SnsException("Unable to determine HTTP method");
    }
}
