<?php

namespace Wetzel\Datamapper\Providers;

use Illuminate\Support\ServiceProvider;
use Wetzel\Datamapper\Metadata\EntityScanner;
use Wetzel\Datamapper\Metadata\EntityValidator;
use Wetzel\Datamapper\Schema\Builder as SchemaBuilder;
use Wetzel\Datamapper\Eloquent\Generator as ModelGenerator;
use Wetzel\Datamapper\Console\SchemaCreateCommand;
use Wetzel\Datamapper\Console\SchemaUpdateCommand;
use Wetzel\Datamapper\Console\SchemaDropCommand;

class SchemaCommandsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register('Wetzel\Datamapper\Providers\MetadataServiceProvider');

        $this->registerEntityScanner();

        $this->registerSchemaBuilder();

        $this->registerModelGenerator();

        $this->registerCommands();
    }

    /**
     * Register the entity scanner implementation.
     *
     * @return void
     */
    protected function registerEntityScanner()
    {
        $app = $this->app;

        $app->singleton('datamapper.entity.scanner', function ($app) {
            $reader = $app['datamapper.annotationreader'];

            $validator = new EntityValidator;

            return new EntityScanner($reader, $validator);
        });
    }

    /**
     * Register the scehma builder implementation.
     *
     * @return void
     */
    protected function registerSchemaBuilder()
    {
        $app = $this->app;

        $app->singleton('datamapper.schema.builder', function ($app) {
            $connection = $app['db']->connection();

            return new SchemaBuilder($connection);
        });
    }

    /**
     * Register the scehma builder implementation.
     *
     * @return void
     */
    protected function registerModelGenerator()
    {
        $app = $this->app;

        $app->singleton('datamapper.eloquent.generator', function ($app) {
            $path = $app['path.storage'] . '/framework/entities';

            return new ModelGenerator($app['files'], $path);
        });
    }

    /**
     * Register all of the migration commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        // create singletons of each command
        $commands = array('Create', 'Update', 'Drop');

        foreach ($commands as $command) {
            $this->{'register'.$command.'Command'}();
        }

        // register commands
        $this->commands(
            'command.schema.create',
            'command.schema.update',
            'command.schema.drop'
        );
    }

    /**
     * Register the "schema:create" command.
     *
     * @return void
     */
    protected function registerCreateCommand()
    {
        $this->app->singleton('command.schema.create', function ($app) {
            return new SchemaCreateCommand(
                $app['datamapper.classfinder'],
                $app['datamapper.entity.scanner'],
                $app['datamapper.schema.builder'],
                $app['datamapper.eloquent.generator'],
                $app['config']['datamapper']
            );
        });
    }

    /**
     * Register the "schema:update" command.
     *
     * @return void
     */
    protected function registerUpdateCommand()
    {
        $this->app->singleton('command.schema.update', function ($app) {
            return new SchemaUpdateCommand(
                $app['datamapper.classfinder'],
                $app['datamapper.entity.scanner'],
                $app['datamapper.schema.builder'],
                $app['datamapper.eloquent.generator'],
                $app['config']['datamapper']
            );
        });
    }

    /**
     * Register the "schema:drop" command.
     *
     * @return void
     */
    protected function registerDropCommand()
    {
        $this->app->singleton('command.schema.drop', function ($app) {
            return new SchemaDropCommand(
                $app['datamapper.classfinder'],
                $app['datamapper.entity.scanner'],
                $app['datamapper.schema.builder'],
                $app['datamapper.eloquent.generator'],
                $app['config']['datamapper']
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
            'datamapper.entity.scanner',
            'datamapper.schema.builder',
            'datamapper.eloquent.generator',
            'command.schema.create',
            'command.schema.update',
            'command.schema.drop',
        ];
    }
}
