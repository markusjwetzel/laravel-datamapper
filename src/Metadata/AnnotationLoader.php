<?php namespace Wetzel\Datamapper\Metadata;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Illuminate\Filesystem\Filesystem;

class AnnotationLoader {

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;
    
    /**
     * Create a new annotation loader instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        $this->path = __DIR__ . '/../Annotations';
    }

    
    /**
     * Register all annotations.
     *
     * @return void
     */
    public function registerAll()
    {
        foreach ($this->files->allFiles($this->path) as $file) {
            AnnotationRegistry::registerFile($file->getRealPath());
        }
    }

}