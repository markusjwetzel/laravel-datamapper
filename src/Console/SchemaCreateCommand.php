<?php namespace Wetzel\Datamapper\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Wetzel\Datamapper\Console\SchemaCommand;

class SchemaCreateCommand extends SchemaCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schema:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database tables from annotations.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->info(PHP_EOL . ' 0% Initializing');

        // get classes
        $classes = $this->getClasses();

        $this->info(' 25% Building metadata');

        // build metadata
        $metadata = $this->scanner->scan($classes);

        $this->info(' 50% Generating entity models');

        // generate eloquent models
        $this->models->generate($metadata, true);

        $this->info(' 75% Building database schema');

        // build schema
        $statements = $this->schema->create($metadata);

        $this->info(PHP_EOL . 'Schema created successfully!');

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
