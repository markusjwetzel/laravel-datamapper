<?php namespace Wetzel\Datamapper;

use Illuminate\Support\ServiceProvider;
use Wetzel\Datamapper\Metadata\ClassFinder;
use Wetzel\Datamapper\Metadata\EntityScanner;
use Wetzel\Datamapper\Metadata\EntityValidator;
use Wetzel\Datamapper\Schema\Builder as SchemaBuilder;
use Wetzel\Datamapper\Eloquent\Generator as ModelGenerator;

use Doctrine\Common\Annotations\AnnotationReader;

use Wetzel\Datamapper\Metadata\AnnotationLoader;
use Illuminate\Filesystem\ClassFinder as FilesystemClassFinder;
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

        $this->registerEntityManager();

        $this->registerClassFinder();

        $this->registerEntityScanner();

        $this->registerSchemaBuilder();

        $this->registerModelGenerator();

        $this->registerCommands();

        $this->registerHelpers();

        $this->registerEloquentModels();
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
     * Register the entity manager implementation.
     *
     * @return void
     */
    protected function registerEntityManager()
    {
        $app = $this->app;

        $app->singleton('datamapper.entitymanager', function($app) {
            $config = $app['config']['datamapper'];

            return new EntityManager($config);
        });
    }

    /**
     * Register the class finder implementation.
     *
     * @return void
     */
    protected function registerClassFinder()
    {
        $app = $this->app;

        $app->singleton('datamapper.classfinder', function($app) {
            $finder = new FilesystemClassFinder;

            return new ClassFinder($finder);
        });
    }

    /**
     * Register the entity scanner implementation.
     *
     * @return void
     */
    protected function registerEntityScanner()
    {
        $app = $this->app;

        $app->singleton('datamapper.entityscanner', function($app) {
            $reader = new AnnotationReader;

            $validator = new EntityValidator;

            $config = $app['config']['datamapper'];

            return new EntityScanner($reader, $validator, $config);
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
            return new SchemaCreateCommand(
                $app['datamapper.classfinder'],
                $app['datamapper.entityscanner'],
                $app['datamapper.schema'],
                $app['datamapper.modelgenerator'],
                $app['config']['datamapper']
            );
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
            return new SchemaUpdateCommand(
                $app['datamapper.classfinder'],
                $app['datamapper.entityscanner'],
                $app['datamapper.schema'],
                $app['datamapper.modelgenerator'],
                $app['config']['datamapper']
            );
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
            return new SchemaDropCommand(
                $app['datamapper.classfinder'],
                $app['datamapper.entityscanner'],
                $app['datamapper.schema'],
                $app['datamapper.modelgenerator'],
                $app['config']['datamapper']
            );
        });
    }

    /**
     * Register helpers.
     *
     * @return void
     */
    protected function registerHelpers()
    {
        require __DIR__ . '/Support/helpers.php';
    }

    /**
     * Load the compiled eloquent entity models.
     *
     * @return void
     */
    protected function registerEloquentModels()
    {
        $files = $this->app['files']->files($this->app['path.storage'] . '/framework/entities');
        
        foreach($files as $file) {
            if ($this->app['files']->extension($file) == '') {
                require $file;
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'datamapper.entitymanager',
            'datamapper.classfinder',
            'datamapper.entityscanner',
            'datamapper.schema',
            'datamapper.modelgenerator',
            'command.schema.create',
            'command.schema.update',
            'command.schema.drop',
        ];
    }

}