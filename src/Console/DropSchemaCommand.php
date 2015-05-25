<?php namespace Wetzel\DataMapper\Console;

use Illuminate\Foundation\Composer;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Filesystem\Filesystem;
use UnexpectedValueException;

class DropSchemaCommand extends BaseCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db-schema:drop';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create migration files from annotations of an entity class';

    /**
     * The migration creator instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * @var \Illuminate\Foundation\Composer
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Illuminate\Database\Migrations\MigrationCreator  $creator
     * @param  \Illuminate\Foundation\Composer  $composer
     * @return void
     */
    public function __construct(MigrationCreator $creator, Composer $composer, Filesystem $files)
    {
        parent::__construct();

        $this->creator = $creator;
        $this->composer = $composer;
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $classname = $this->input->getArgument('classname');
        
        $this->writeMigration($classname);

        $this->composer->dumpAutoloads();
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function writeMigration($classname)
    {
        $classname = '\\'.str_replace('/', '\\', $classname);
        if (class_exists($classname)) {
            $class = new $classname();
        } elseif (class_exists('Examunity\Domain'.$classname)) {
            $classname = 'Examunity\Domain'.$classname;
            $class = new $classname();
        } else {
            throw new UnexpectedValueException('Classname is not valid.');
        }

        $all = $this->input->getOption('all');

        $tablename = $class->getTable();

        $name = $tablename."__table";

        $path = $this->getMigrationPath();

        $file = $this->creator->create($name, $path, $tablename, true);

        // delete timestamp from filename
        $this->files->move($file, $path.'/______'.$name.'.php');

        $this->line("<info>Created Migration:</info> $file");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('classname', InputArgument::REQUIRED, 'The classname for the migration'),
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
            array('all', null, InputOption::VALUE_NONE, 'Search for all eloquent models.'),
        );
    }

}
