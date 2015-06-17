<?php

namespace Wetzel\Datamapper\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Wetzel\Datamapper\Console\SchemaCommand;

class SchemaDropCommand extends SchemaCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schema:drop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop database tables.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // get classes
        $classes = $this->getClasses($this->config['models_namespace']);

        // build metadata
        $metadata = $this->scanner->scan($classes, $this->config['namespace_tablenames'], $this->config['morphclass_abbreviations']);

        // clean generated eloquent models
        $this->models->clean();

        // build schema
        $statements = $this->schema->drop($metadata);

        $this->info('Schema dropped successfully!');

        // output SQL queries
        if ($this->option('dump-sql')) {
            $this->outputQueries($statements);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('class', InputArgument::OPTIONAL, 'The classname for the migration'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('dump-sql', null, InputOption::VALUE_NONE, 'Search for all eloquent models.'),
        );
    }
}
