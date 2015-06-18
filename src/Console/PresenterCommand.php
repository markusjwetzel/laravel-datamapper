<?php

namespace Wetzel\Datamapper\Console;

use Illuminate\Console\Command;
use Wetzel\Datamapper\Metadata\ClassFinder;
use Wetzel\Datamapper\Metadata\PresenterScanner;
use Wetzel\Datamapper\Presenter\Repository;
use UnexpectedValueException;

abstract class PresenterCommand extends Command
{
    /**
     * The class finder instance.
     *
     * @var \Wetzel\Datamapper\Metadata\ClassFinder
     */
    protected $finder;

    /**
     * The presenter scanner instance.
     *
     * @var \Wetzel\Datamapper\Metadata\PresenterScanner
     */
    protected $scanner;

    /**
     * The presenter repository instance.
     *
     * @var \Wetzel\Datamapper\Presenter\Repository
     */
    protected $repository;

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
     * @param \Wetzel\Datamapper\Metadata\PresenterScanner $scanner
     * @param \Wetzel\Datamapper\Presenter\Repository $schema
     * @param array $config
     * @return void
     */
    public function __construct(ClassFinder $finder, PresenterScanner $scanner, Repository $repository, $config)
    {
        parent::__construct();

        $this->finder = $finder;
        $this->scanner = $scanner;
        $this->repository = $repository;
        $this->config = $config;
    }
}