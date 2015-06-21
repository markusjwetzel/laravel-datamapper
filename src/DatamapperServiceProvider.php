<?php

namespace ProAI\Datamapper;

use Illuminate\Support\ServiceProvider;
use ProAI\Datamapper\Presenter\Repository;
use ProAI\Datamapper\Presenter\Decorator;

class DatamapperServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $app = $this->app;

        $app['view']->composer('*', function ($view) use ($app) {
            $data = array_merge($view->getFactory()->getShared(), $view->getData());

            foreach ($data as $key => $item) {
                $view[$key] = Decorator::decorate($item);
            }
        });
    }

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

        $this->registerPresenters();

        $this->registerEloquentModels();

        $this->app->register('ProAI\Datamapper\Providers\SchemaCommandsServiceProvider');

        $this->app->register('ProAI\Datamapper\Providers\PresenterCommandsServiceProvider');
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
            $config = $app['config']['datamapper'];

            return new EntityManager($config);
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
     * Register all presenters.
     *
     * @return void
     */
    protected function registerPresenters()
    {
        $app = $this->app;

        $app->singleton('datamapper.presenter.repository', function ($app) {
            $path = $app['path.storage'] . '/framework/presenters.json';

            return new Repository($app['files'], $path);
        });

        $app['datamapper.presenter.repository']->load();
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
