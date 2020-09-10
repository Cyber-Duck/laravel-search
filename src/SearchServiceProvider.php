<?php

namespace CyberDuck\Search;

use CyberDuck\Search\Console\DeleteCommand;
use CyberDuck\Search\Console\FlushCommand;
use CyberDuck\Search\Console\ImportCommand;
use CyberDuck\Search\Console\ResetCommand;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {

        $this->mergeConfigFrom(__DIR__.'/../config/laravel-search.php', 'laravel-search');

        $this->app->singleton(EngineManager::class, function ($app) {
            return new EngineManager($app);
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCommand::class,
                FlushCommand::class,
                DeleteCommand::class,
                ResetCommand::class
            ]);

            $this->publishes([
                __DIR__.'/../config/laravel-search.php' => config_path('laravel-search.php'),
            ], ['config', 'laravel-search-config']);
        }

        $this->app->singleton('search', function ($app) {
            return new Search();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['search'];
    }
}
