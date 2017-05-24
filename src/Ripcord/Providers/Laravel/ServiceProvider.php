<?php

namespace Ripcord\Providers\Laravel;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Ripcord\Providers\Laravel\Console\PublishCommand;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * @var string
     */
    protected $service_name = 'ripcord';

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__.'/config/'.$this->service_name.'.php';
        $this->publishes([
            $configPath => config_path($this->service_name.'.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.ripcord.publish', function () {
            return new PublishCommand();
        });

        $this->commands(
            'command.ripcord.publish'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.ripcord.publish',
        ];
    }
}
