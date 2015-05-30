<?php namespace Wetzel\Datamapper\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Wetzel\Datamapper\Metadata\Builder;
use Wetzel\Datamapper\Schema\Builder;
use UnexpectedValueException;

class SchemaCreateCommand extends Command {

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
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationCreator  $creator
     * @param  \Illuminate\Foundation\Composer  $composer
     * @return void
     */
    public function __construct(Metadata $metadata, Schema $schema)
    {
        parent::__construct();

        $this->metadata = $metadata;
        $this->schema = $schema;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $class = $this->argument('class');
        
        $this->createSchema($class);
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function createSchema($class)
    {
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

        if ($this->option('sql')) {
            $this->info('Outputting queries:');
            $sql = $this->schema->create($this->metadata->getMetadata($classes), true);
            $this->info(implode(';' . PHP_EOL, $sql));
        } else {
            $this->info('Creating database schema...');
            $this->schema->create($this->metadata->getMetadata($classes));
            $this->info('Schema has been created!');
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
        );
    }

}
