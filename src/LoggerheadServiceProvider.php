<?php

namespace Clumsy\Loggerhead;

use Illuminate\Support\ServiceProvider;

class LoggerheadServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['clumsy.loggerhead'] = $this->app->make(Loggerhead::class);

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'clumsy/loggerhead');

        $this->loadViewsFrom(__DIR__.'/resources/views', 'clumsy/loggerhead');

        $this->registerPublishers();
    }

    protected function registerCommands()
    {
        $this->app['command.clumsy.trigger-pending-notifications'] = $this->app->share(function ($app) {
            return new Console\TriggerPendingNotificationsCommand();
        });

        $this->commands([
            'command.clumsy.trigger-pending-notifications',
        ]);
    }

    protected function registerPublishers()
    {
        $this->publishes([
            __DIR__.'/resources/lang' => base_path('resources/lang/vendor/clumsy/loggerhead'),
        ], 'translations');

        $this->publishes([
            __DIR__.'/resources/views' => base_path('resources/views/vendor/clumsy/loggerhead'),
        ], 'views');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations')
        ], 'migrations');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'clumsy.loggerhead',
        );
    }
}
