<?php


namespace Nipwaayoni\SnsHandler\Providers;


use Illuminate\Support\ServiceProvider;
use Nipwaayoni\SnsHandler\Events\SnsConfirmationRequestReceived;
use Nipwaayoni\SnsHandler\Listeners\SnsConfirmationRequestListener;


class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SnsConfirmationRequestReceived::class => [
            SnsConfirmationRequestListener::class,
        ]
    ];

}