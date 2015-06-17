<?php

namespace Wetzel\Datamapper\Console;

use Illuminate\Console\Command;
use Wetzel\Datamapper\Metadata\ClassFinder;
use Wetzel\Datamapper\Metadata\EntityScanner;
use Wetzel\Datamapper\Schema\Builder as SchemaBuilder;
use Wetzel\Datamapper\Eloquent\Generator as ModelGenerator;
use UnexpectedValueException;

abstract class SchemaCommand extends Command
{
    /**
     * The class finder instance.
     *
     * @var \Wetzel\Datamapper\Metadata\ClassFinder
     */
    protected $finder;

    /**
     * The entity scanner instance.
     *
     * @var \Wetzel\Datamapper\Metadata\EntityScanner
     */
    protected $scanner;

    /**
     * The schema builder instance.
     *
     * @var \Wetzel\Datamapper\Schema\Builder
     */
    protected $schema;

    /**
     * The schema builder instance.
     *
     * @var \Wetzel\Datamapper\Eloquent\Generator
     */
    protected $modelGenerator;

    /**
     * The config of the datamapper package.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new migration install command instance.
     *
     * @param \Wetzel\Datamapper\Metadata\ClassFinder $finder
     * @param \Wetzel\Datamapper\Metadata\EntityScanner $scanner
     * @param \Wetzel\Datamapper\Schema\Builder $schema
     * @param \Wetzel\Datamapper\Eloquent\Generator $models
     * @param array $config
     * @return void
     */
    public function __construct(ClassFinder $finder, EntityScanner $scanner, SchemaBuilder $schema, ModelGenerator $models, $config)
    {
        parent::__construct();

        $this->finder = $finder;
        $this->scanner = $scanner;
        $this->schema = $schema;
        $this->models = $models;
        $this->config = $config;
    }

    /**
     * Get classes by class argument or by app namespace.
     *
     * @return void
     */
    protected function getClasses()
    {
        $class = $this->argument('class');

        // set classes
        if ($class) {
            if (class_exists($class)) {
                $classes = [$class];
            } elseif (class_exists($this->config['models_namespace'] . '\\' . $class)) {
                $classes = [$this->config['models_namespace'] . '\\' . $class];
            } else {
                throw new UnexpectedValueException('Classname is not valid.');
            }
        } else {
            $classes = $this->finder->getClassesFromNamespace($this->config['models_namespace']);
        }

        return $classes;
    }

    /**
     * Output SQL queries.
     *
     * @param array $statements SQL statements
     * @return void
     */
    protected function outputQueries($statements)
    {
        $this->info(PHP_EOL . 'Outputting queries:');
        if (empty($statements)) {
            $this->info("No queries found.");
        } else {
            $this->info(implode(';' . PHP_EOL, $statements));
        }
    }
}
