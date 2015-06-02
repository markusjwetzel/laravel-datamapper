<?php namespace Wetzel\Datamapper\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Wetzel\Datamapper\Console\SchemaCommand;

class SchemaUpdateCommand extends SchemaCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schema:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update database tables from annotations.';

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
        $metadataArray = $this->metadata->build($classes);

        $this->info(' 50% Generating entity models');

        // generate eloquent models
        $this->models->generate($metadataArray, $this->option('save-mode'));

        $this->info(' 75% Building database schema');

        // build schema
        $statements = $this->schema->update($metadataArray, $this->option('save-mode'));

        $this->info(PHP_EOL . 'Schema updated successfully!');

        // output SQL queries
        if ($this->option('sql')) {
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
            array('sql', null, InputOption::VALUE_NONE, 'Search for all eloquent models.'),
            array('save-mode', null, InputOption::VALUE_NONE, 'Doctrine DBAL save mode for updating.'),
        );
    }

}
