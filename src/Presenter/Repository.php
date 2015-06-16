<?php

namespace Wetzel\Datamapper\Presenter;

use Illuminate\Filesystem\Filesystem;

class Repository
{
    /**
     * The presentable classes.
     *
     * @var array
     */
    public static $presenters = [];

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path to the presenters mapping file.
     *
     * @var string
     */
    protected $path;

    /**
     * Create a new presenters repository instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $path
     * @return void
     */
    public function __construct(Filesystem $files, $path)
    {
        $this->files = $files;
        $this->path = $path;
    }

    /**
     * Load the presenters mapping JSON file.
     *
     * @return void
     */
    public function load()
    {
        if ($this->files->exists($this->path)) {
            static::$presenters = json_decode($this->files->get($this->path), true);
        }
    }

    /**
     * Write the presenters mapping file to disk.
     *
     * @return void
     */
    public function save()
    {
        $this->files->put(
            $this->path, json_encode(static::$presenters, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Delete the presenters mapping file.
     *
     * @return void
     */
    public function delete()
    {
        $this->files->delete(
            $this->path
        );
    }

    /**
     * Set the presenters.
     *
     * @param  array  $presenters
     * @return array
     */
    public function set($presenters)
    {
        static::$presenters = $presenters;
    }

    /**
     * Set the presenters.
     *
     * @param  string  $presenter
     * @return array
     */
    public function add($presenter)
    {
        static::$presenters[] = $presenter;
    }

    /**
     * Get the presenters.
     *
     * @return array
     */
    public function get()
    {
        return static::$presenters;
    }
}
