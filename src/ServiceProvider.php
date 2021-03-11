<?php


namespace MiamiOH\SnsHandler;


use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use MiamiOH\SnsHandler\Controllers\SnsMessageController;

/**
 * Class ServiceProvider
 * @package MiamiOH\SnsHandler
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
        $this->mergeConfigFrom($this->configPath, 'sns-handler');
        $this->app->bind(SnsTopicMapper::class, function () {
            return new SnsTopicMapper(config('sns-handler.sns-class-map', []));
        });
    }
}
