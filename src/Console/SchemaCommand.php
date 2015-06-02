<?php namespace Wetzel\Datamapper\Console;

use Illuminate\Console\Command;

use Wetzel\Datamapper\Metadata\Builder as MetadataBuilder;
use Wetzel\Datamapper\Schema\Builder as SchemaBuilder;
use Wetzel\Datamapper\Eloquent\Generator as ModelGenerator;
use UnexpectedValueException;

abstract class SchemaCommand extends Command {

    /**
     * The metadata builder instance.
     *
     * @var \Wetzel\Datamapper\Metadata\Builder
     */
    protected $metadata;

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
     * Create a new migration install command instance.
     *
     * @param  \Wetzel\Datamapper\Metadata\Builder $metadata
     * @param  \Wetzel\Datamapper\Schema\Builder $schema
     * @return void
     */
    public function __construct(MetadataBuilder $metadata, SchemaBuilder $schema, ModelGenerator $models)
    {
        parent::__construct();

        $this->metadata = $metadata;
        $this->schema = $schema;
        $this->models = $models;
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
            } else {
                throw new UnexpectedValueException('Classname is not valid.');
            }
        } else {
            $classes = $this->metadata->getClassesFromNamespace();
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
