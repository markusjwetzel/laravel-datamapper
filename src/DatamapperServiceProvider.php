<?php namespace Wetzel\Datamapper;

use Illuminate\Support\ServiceProvider;
use Wetzel\Datamapper\Metadata\Builder as MetadataBuilder;
use Wetzel\Datamapper\Schema\Builder as SchemaBuilder;
use Wetzel\Datamapper\Eloquent\Generator as ModelGenerator;

use Doctrine\Common\Annotations\AnnotationReader;

use Wetzel\Datamapper\Metadata\AnnotationLoader;
use Illuminate\Filesystem\ClassFinder;
use Wetzel\Datamapper\Console\SchemaCreateCommand;
use Wetzel\Datamapper\Console\SchemaUpdateCommand;
use Wetzel\Datamapper\Console\SchemaDropCommand;

class DatamapperServiceProvider extends ServiceProvider {

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

        $this->registerAnnotations();

        $this->registerMetadataBuilder();

        $this->registerSchemaBuilder();

        $this->registerModelGenerator();

        $this->registerCommands();

        $this->loadEloquentModels();
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
     * Registers all annotation classes
     *
     * @return void
     */
    public function registerAnnotations()
    {
        $app = $this->app;

        $loader = new AnnotationLoader($app['files']);

        $loader->registerAll();
    }

    /**
     * Register the metadata builder implementation.
     *
     * @return void
     */
    protected function registerMetadataBuilder()
    {
        $app = $this->app;

        $app->singleton('datamapper.metadata', function($app) {
            $reader = new AnnotationReader;

            $finder = new ClassFinder;

            return new MetadataBuilder($reader, $finder);
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

        $app->singleton('datamapper.schema', function($app) {
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

        $app->singleton('datamapper.modelgenerator', function($app) {
            return new ModelGenerator($app['files'], $app['path.storage']);
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
            $this->{'registerSchema'.$command.'Command'}();
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
    protected function registerSchemaCreateCommand()
    {
        $this->app->singleton('command.schema.create', function($app) {
            return new SchemaCreateCommand($app['datamapper.metadata'], $app['datamapper.schema'], $app['datamapper.modelgenerator']);
        });
    }

    /**
     * Register the "schema:update" command.
     *
     * @return void
     */
    protected function registerSchemaUpdateCommand()
    {
        $this->app->singleton('command.schema.update', function($app) {
            return new SchemaUpdateCommand($app['datamapper.metadata'], $app['datamapper.schema'], $app['datamapper.modelgenerator']);
        });
    }

    /**
     * Register the "schema:drop" command.
     *
     * @return void
     */
    protected function registerSchemaDropCommand()
    {
        $this->app->singleton('command.schema.drop', function($app) {
            return new SchemaDropCommand($app['datamapper.metadata'], $app['datamapper.schema'], $app['datamapper.modelgenerator']);
        });
    }

    /**
     * Load the compiled eloquent entity models.
     *
     * @return void
     */
    protected function loadEloquentModels()
    {
        $files = $this->app['files']->files($this->app['path.storage'] . '/framework/entities');
        
        foreach($files as $file) {
            require $file;
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['datamapper'];
    }

}