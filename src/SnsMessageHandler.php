<?php


namespace MiamiOH\SnsHandler;

interface SnsMessageHandler
{
    public function handle(SnsMessage $message): void;
}
