<?php

namespace Ripcord\Providers\Laravel;

use Illuminate\Support\ServiceProvider;
use Ripcord\Providers\Laravel\Console\PublishCommand;

class ServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     *
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

        $configPath = __DIR__ . 'config/'.$this->service_name.'.php';
        $this->publishes([
            $configPath => config_path($this->service_name.'.php')
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $service_name = $this->service_name;

        $this->app->singleton(Ripcord::class, function($app) use ($service_name)
        {
            $config = $app['config'][$service_name];

            return new Ripcord(
                $config['url'],
                $config['db'],
                $config['user'],
                $config['password']
            );
        });

        $this->app['command.ripcord.publish'] = $this->app->share(
            function () {
                return new PublishCommand();
            }
        );

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
            'command.ripcord.publish'
        ];
    }
}
