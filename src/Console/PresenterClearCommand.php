<?php namespace Wetzel\Datamapper\Console;

use Wetzel\Datamapper\Console\PresenterCommand;

class PresenterClearCommand extends PresenterCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'presenter:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all registered presenters.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // save empty array to clean presenters file
        $this->repository->set([]);
        $this->repository->save();

        $this->info('Presenters cleared successfully!');
    }

}
