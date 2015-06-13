<?php namespace Wetzel\Datamapper;

use Illuminate\Support\ServiceProvider;

use Wetzel\Datamapper\Presenter\Repository;
use Wetzel\Datamapper\Console\PresenterRegisterCommand;
use Wetzel\Datamapper\Console\PresenterClearCommand;

class PresenterServiceProvider extends ServiceProvider {

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['view']->composer('*', function ($view) use ($app) {
            $data = array_merge($view->getFactory()->getShared(), $view->getData());

            foreach ($data as $key => $value) {
                if ($value instanceof \Wetzel\Datamapper\Contracts\Presentable) {
                    $view[$key] = $value->getPresenter();
                }
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
        $this->registerPresenters();

        $this->registerCommands();
    }

    /**
     * Register all presenters.
     *
     * @return void
     */
    protected function registerPresenters()
    {
        $path = $app['path.storage'].'/framework'

        $repositoy = new Repository($app['files'], $path);

        $repository->load();
    }

    /**
     * Register all of the migration commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        // create singletons of each command
        $commands = array('Register', 'Clear');

        foreach ($commands as $command) {
            $this->{'registerPresenter'.$command.'Command'}();
        }

        // register commands
        $this->commands(
            'command.presenter.register',
            'command.presenter.clear'
        );
    }

    /**
     * Register the "presenter:register" command.
     *
     * @return void
     */
    protected function registerPresenterRegisterCommand()
    {
        $this->app->singleton('command.presenter.register', function($app) {
            return new PresenterRegisterCommand(
                $app['datamapper.classfinder'],
                $app['datamapper.presenter.scanner'],
                $app['datamapper.presenter.repository']
            );
        });
    }

    /**
     * Register the "presenter:clear" command.
     *
     * @return void
     */
    protected function registerPresenterClearCommand()
    {
        $this->app->singleton('command.presenter.clear', function($app) {
            return new PresenterClearCommand(
                $app['datamapper.classfinder'],
                $app['datamapper.presenter.scanner'],
                $app['datamapper.presenter.repository']
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.presenter.register',
            'command.presenter.clear'
        ];
    }

}