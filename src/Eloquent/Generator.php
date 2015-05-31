<?php namespace Wetzel\Datamapper\Eloquent;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\AppNamespaceDetectorTrait;

class Generator {

    use AppNamespaceDetectorTrait;

    /**
     * The filesystem instance.
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Model stubs.
     * @var array
     */
    protected $stubs;

    /**
     * Constructor.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        $this->stubs['model'] = $this->files->get(__DIR__ . '/../../stubs/model.stub');
        $this->stubs['relation'] = $this->files->get(__DIR__ . '/../../stubs/model-relation.stub');
    }

    /**
     * Generate model from metadata.
     *
     * @param array $metadataArray
     * @return string
     */
    public function generate($metadataArray)
    {
        foreach($metadataArray as $metadata) {
            $this->generateModel($metadata);
        }
    }

    /**
     * Generate model from metadata.
     *
     * @param array $metadata
     * @return string
     */
    public function generateModel($metadata)
    {
        $stub = $this->stubs['model'];

        //$this->replaceSoftDeletes($metadata['softDeletes'], $stub);
        $this->replaceTable($metadata['table']['name'], $stub);
        //$this->replacePrimaryKey($metadata['primarykey'], $stub);
        //$this->replaceIncrementing($metadata['incrementing'], $stub);
        $this->replaceTimestamps($metadata['timestamps'], $stub);
        $this->replaceAttributes($metadata['attributes'], $stub);
        $this->replaceEmbeddeds($metadata['embeddeds'], $stub);
        //$this->replaceHidden($metadata['hidden'], $stub);
        //$this->replaceVisible($metadata['visible'], $stub);
        //$this->replaceAppends($metadata['appends'], $stub);
        //$this->replaceFillable($metadata['fillable'], $stub);
        //$this->replaceDates($metadata['dates'], $stub);
        //$this->replaceTouches($metadata['touches'], $stub);
        $this->replaceRelations($metadata['relations'], $stub);

        //$this->files->put($path, $this->buildClass($name));
        
        dd($stub);

        return $stub;
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $class
     * @param  string  $stub
     * @return void
     */
    protected function replaceNamespace($class, &$stub)
    {
        $stub = str_replace(
            '{{namespace}}', $this->getNamespace($name), $stub
        );

        $stub = str_replace(
            '{{rootNamespace}}', $this->getAppNamespace(), $stub
        );
    }
    
    /**
     * Replaces soft deletes.
     *
     * @param boolean $option
     * @param string $stub
     * @return void
     */
    protected function replaceSoftDeletes($option, &$stub)
    {
        $stub = str_replace('{{softDeletes}}', $option ? 'use SoftDeletes;' : '' , $stub);
    }
    
    /**
     * Replaces table name.
     * 
     * @param boolean $name
     * @param string $stub
     * @return void
     */
    protected function replaceTable($name, &$stub)
    {
        $stub = str_replace('{{table}}', "'".$name."'", $stub);
    }
    
    /**
     * Replaces primary key.
     * 
     * @param string $name
     * @param string $stub
     * @return void
     */
    protected function replacePrimaryKey($name, &$stub)
    {
        $stub = str_replace('{{primarykey}}', "'".$name."'", $stub);
    }
    
    /**
     * Replaces incrementing.
     * 
     * @param boolean $option
     * @param string $stub
     * @return void
     */
    protected function replaceIncrementing($option, &$stub)
    {
        $stub = str_replace('{{incrementing}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces timestamps.
     * 
     * @param boolean $option
     * @param string $stub
     * @return void
     */
    protected function replaceTimestamps($option, &$stub)
    {
        $stub = str_replace('{{timestamps}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces attributes.
     * 
     * @param array $attributes
     * @param string $stub
     * @return void
     */
    protected function replaceAttributes($attributes, &$stub)
    {
        //$stub = str_replace('{{attributes}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces embeddeds.
     * 
     * @param array $embeddeds
     * @param string $stub
     * @return void
     */
    protected function replaceEmbeddeds($embeddeds, &$stub)
    {
        //$stub = str_replace('{{embeddeds}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces hidden.
     * 
     * @param array $hidden
     * @param string $stub
     * @return void
     */
    protected function replaceHidden($hidden, &$stub)
    {
        //$stub = str_replace('{{hidden}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces visible.
     * 
     * @param array $visible
     * @param string $stub
     * @return void
     */
    protected function replaceVisible($visible, &$stub)
    {
        //$stub = str_replace('{{visible}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces appends.
     * 
     * @param array $appends
     * @param string $stub
     * @return void
     */
    protected function replaceAppends($appends, &$stub)
    {
        //$stub = str_replace('{{appends}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces fillable.
     * 
     * @param array $fillable
     * @param string $stub
     * @return void
     */
    protected function replaceFillable($fillable, &$stub)
    {
        //$stub = str_replace('{{fillable}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces dates.
     * 
     * @param array $dates
     * @param string $stub
     * @return void
     */
    protected function replaceDates($dates, &$stub)
    {
        //$stub = str_replace('{{dates}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces touches.
     * 
     * @param array $touches
     * @param string $stub
     * @return void
     */
    protected function replaceTouches($touches, &$stub)
    {
        //str_replace('{{touches}}', $option ? 'true' : 'false', $stub);
    }
    
    /**
     * Replaces relations.
     * 
     * @param array $relations
     * @param string $stub
     * @return void
     */
    protected function replaceRelations($relations, &$stub)
    {
        $relations = [];

        foreach($relations as $relation) {
            $relationStub = $this->stubs['relation'];

            $options = implode(",",array_merge($relation['related'], $relation['options']));
            // todo: merge by relation

            $relationStub = str_replace('{{name}}', $relation['name'], $relationStub);
            $relationStub = str_replace('{{options}}', $options, $relationStub);
            $relationStub = str_replace('{{ucfirst_type}}', ucfirst($relation['type']), $relationStub);
            $relationStub = str_replace('{{type}}', $relation['type'], $relationStub);

            $relations[] = $relationStub;
        }

        $stub = str_replace('{{relations}}', implode(PHP_EOL . PHP_EOL, $relations), $stub);
    }

    /**
     * Get the full namespace name for a given class.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Get class name.
     *
     * @param string $class
     * @return string
     */
    protected function getClassname($class)
    {
        $items = explode('\\', $class);

        $class = array_pop($items);

        return $class;
    }

}