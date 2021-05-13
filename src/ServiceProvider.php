<?php


namespace Nipwaayoni\SnsHandler;

use Aws\Sns\MessageValidator;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Nipwaayoni\SnsHandler\Controllers\SnsMessageController;
use Nipwaayoni\SnsHandler\Providers\EventServiceProvider;

/**
 * Class ServiceProvider
 * @package Nipwaayoni\SnsHandler
 *
 * @codeCoverageIgnore
 */
class ServiceProvider extends BaseServiceProvider
{
    private $configPath = __DIR__ . '/../config/sns-handler.php';

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->publishes([
            $this->configPath => config_path('sns-handler.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make(SnsMessageController::class);

        $this->app->register(EventServiceProvider::class);

        $this->mergeConfigFrom($this->configPath, 'sns-handler');

        if (!config('sns-handler.validate-sns-messages')) {
            $this->app->bind(MessageValidator::class, NullMessageValidator::class);
        }

        $this->app->bind(SnsTopicMapper::class, function () {
            return new SnsTopicMapper(config('sns-handler.sns-class-map', []));
        });
    }
}
