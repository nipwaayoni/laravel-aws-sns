<?php


namespace Nipwaayoni\SnsHandler;

interface SnsMessageHandler
{
    public function handle(SnsMessage $message): void;
}
