<?php

namespace ProAI\Datamapper;

use Illuminate\Support\ServiceProvider;

class DatamapperServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

        $this->registerEntityManager();

        $this->registerHelpers();

        $this->registerEloquentModels();

        $this->app->register('ProAI\Datamapper\Providers\CommandsServiceProvider');
    }

    /**
     * Register the config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $configPath = __DIR__ . '/../config/datamapper.php';

        $this->mergeConfigFrom($configPath, 'datamapper');

        $this->publishes([$configPath => config_path('datamapper.php')], 'config');
    }

    /**
     * Register the entity manager implementation.
     *
     * @return void
     */
    protected function registerEntityManager()
    {
        $app = $this->app;

        $app->singleton('datamapper.entitymanager', function ($app) {
            return new EntityManager;
        });
    }

    /**
     * Register helpers.
     *
     * @return void
     */
    protected function registerHelpers()
    {
        require_once __DIR__ . '/Support/helpers.php';
    }

    /**
     * Load the compiled eloquent entity models.
     *
     * @return void
     */
    protected function registerEloquentModels()
    {
        $files = $this->app['files']->files($this->app['path.storage'] . '/framework/entities');
        
        foreach ($files as $file) {
            if ($this->app['files']->extension($file) == '') {
                require_once $file;
            }
        }
    }
}
