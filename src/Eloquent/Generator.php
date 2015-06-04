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
     * Path to model storage directory.
     * @var array
     */
    protected $path;

    /**
     * Model stubs.
     * @var array
     */
    protected $stubs;

    /**
     * Constructor.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param string $storagePath
     * @return void
     */
    public function __construct(Filesystem $files, $storagePath)
    {
        $this->files = $files;
        $this->path = $storagePath . '/framework';

        $this->stubs['model'] = $this->files->get(__DIR__ . '/../../stubs/model.stub');
        $this->stubs['relation'] = $this->files->get(__DIR__ . '/../../stubs/model-relation.stub');
    }

    /**
     * Generate model from metadata.
     *
     * @param array $metadataArray
     * @param boolean $saveMode
     * @return void
     */
    public function generate($metadataArray, $saveMode=false)
    {
        // clean or make (if not exists) model storage directory
        if ( ! $this->files->exists($this->path . '/entities')) {
            $this->files->makeDirectory($this->path . '/entities');
        }

        // clear existing models if save mode is off
        if ( ! $saveMode) {
            $this->clean();
        }

        // create models
        foreach($metadataArray as $metadata) {
            $this->generateModel($metadata);
        }

        // create json file for metadata
        $contents = json_encode($metadataArray, JSON_PRETTY_PRINT);
        $this->files->put($this->path . '/entities.json', $contents);
    }

    /**
     * Clean model directory.
     *
     * @return void
     */
    public function clean()
    {
        if ($this->files->exists($this->path . '/entities')) {
            $this->files->cleanDirectory($this->path . '/entities');
        }
    }

    /**
     * Generate model from metadata.
     *
     * @param array $metadata
     * @return void
     */
    public function generateModel($metadata)
    {
        $stub = $this->stubs['model'];

        $classname = md5($metadata['class']);

        // header
        $this->replaceNamespace('Wetzel\Datamapper\Cache', $stub);
        $this->replaceClass('Entity' . $classname, $stub);
        $this->replaceMappedClass($metadata['class'], $stub);

        // softDeletes
        $this->replaceSoftDeletes($metadata['softDeletes'], $stub);

        // table name
        $this->replaceTable($metadata['table']['name'], $stub);

        // primary key
        list($primaryKey, $incrementing) = $this->getPrimaryKey($metadata);
        $this->replacePrimaryKey($primaryKey, $stub);
        $this->replaceIncrementing($incrementing, $stub);

        // timestamps
        $this->replaceTimestamps($metadata['timestamps'], $stub);

        // misc
        $this->replaceHidden($metadata['hidden'], $stub);
        $this->replaceVisible($metadata['visible'], $stub);
        $this->replaceTouches($metadata['touches'], $stub);

        // mapping data
        $mapping = $this->generateMappingData($metadata);
        $this->replaceMapping($mapping, $stub);
        
        // relations
        $this->replaceRelations($metadata['relations'], $stub);

        $this->files->put($this->path . '/entities/' . $classname, $stub);
    }

    /**
     * Get primary key and auto increment value.
     *
     * @param  array  $metadata
     * @return array
     */
    protected function getPrimaryKey($metadata)
    {
        $primaryKey = 'id';
        $incrementing = true;

        foreach($metadata['table']['columns'] as $column) {
            if ( ! empty($column['primary'])) {
                $primaryKey = $column['name'];
                $incrementing = ( ! empty($column['options']['autoIncrement']));
            }
        }

        return [$primaryKey, $incrementing];
    }

    /**
     * Generate mapping array.
     *
     * @param  array  $metadata
     * @return array
     */
    protected function generateMappingData($metadata)
    {
        $attributes = [];
        foreach($metadata['attributes'] as $attributeMetadata) {
            $attributes[] = $attributeMetadata['name'];
        }

        $embeddeds = [];
        foreach($metadata['embeddeds'] as $embeddedMetadata) {
            $embedded = [];
            $embedded['class'] = $embeddedMetadata['class'];
            $embeddedAttributes = [];
            foreach($embeddedMetadata['attributes'] as $attributeMetadata) {
                $embeddedAttributes[] = $attributeMetadata['name'];
            }
            $embedded['attributes'] = $embeddedAttributes;
            $embeddeds[$embeddedMetadata['name']] = $embedded;
        }

        $relations = [];
        foreach($metadata['relations'] as $relationMetadata) {
            $relation = [];
            if ( ! empty($relationMetadata['relatedClass'])) {
                $relation['relatedClass'] = $relationMetadata['relatedClass'];
                $relation['mappedRelatedClass'] = 'Wetzel\Datamapper\Cache\Entity' . md5($relationMetadata['relatedClass']);
            } else {
                $relation['relatedClass'] = null;
                $relation['mappedRelatedClass'] = null;
            }
            $relations[$relationMetadata['name']] = $relation;
        }

        return [
            'attributes' => $attributes,
            'embeddeds' => $embeddeds,
            'relations' => $relations,
        ];
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @return void
     */
    protected function replaceNamespace($name, &$stub)
    {
        $stub = str_replace('{{namespace}}', $name, $stub);
    }

    /**
     * Replace the classname for the given stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @return void
     */
    protected function replaceClass($name, &$stub)
    {
        $stub = str_replace('{{class}}', $name, $stub);
    }

    /**
     * Replace the classname for the given stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @return void
     */
    protected function replaceMappedClass($name, &$stub)
    {
        $stub = str_replace('{{mappedClass}}', "'".$name."'", $stub);
    }
    
    /**
     * Replace soft deletes.
     *
     * @param boolean $option
     * @param string $stub
     * @return void
     */
    protected function replaceSoftDeletes($option, &$stub)
    {
        $stub = str_replace('{{softDeletes}}', $option ? 'use SoftDeletes;' . PHP_EOL . PHP_EOL . '    ' : '' , $stub);
    }
    
    /**
     * Replace table name.
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
     * Replace primary key.
     * 
     * @param string $name
     * @param string $stub
     * @return void
     */
    protected function replacePrimaryKey($name, &$stub)
    {

        $stub = str_replace('{{primaryKey}}', "'".$name."'", $stub);
    }
    
    /**
     * Replace incrementing.
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
     * Replace timestamps.
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
     * Replace hidden.
     * 
     * @param array $hidden
     * @param string $stub
     * @return void
     */
    protected function replaceHidden($hidden, &$stub)
    {
        $stub = str_replace('{{hidden}}', $this->getArrayAsText($hidden), $stub);
    }
    
    /**
     * Replace visible.
     * 
     * @param array $visible
     * @param string $stub
     * @return void
     */
    protected function replaceVisible($visible, &$stub)
    {
        $stub = str_replace('{{visible}}', $this->getArrayAsText($visible), $stub);
    }
    
    /**
     * Replace touches.
     * 
     * @param array $touches
     * @param string $stub
     * @return void
     */
    protected function replaceTouches($touches, &$stub)
    {
        $stub = str_replace('{{touches}}', $this->getArrayAsText($touches), $stub);
    }
    
    /**
     * Replace mapping.
     * 
     * @param array $mapping
     * @param string $stub
     * @return void
     */
    protected function replaceMapping($mapping, &$stub)
    {
        $stub = str_replace('{{mapping}}', $this->getArrayAsText($mapping), $stub);
    }
    
    /**
     * Replace relations.
     * 
     * @param array $relations
     * @param string $stub
     * @return void
     */
    protected function replaceRelations($relations, &$stub)
    {
        $textRelations = [];

        foreach($relations as $relation) {
            $relationStub = $this->stubs['relation'];

            // generate options array
            $options = [];

            if ($relation['type'] != 'morphTo') {
                $options[] = "'".'\Wetzel\Datamapper\Cache\Entity' . md5($relation['relatedClass'])."'";
            }

            foreach($relation['options'] as $option) {
                if ($option === null) {
                    $options[] = 'null';
                } elseif ($option === true) {
                    $options[] = 'true';
                } elseif ($option === false) {
                    $options[] = 'false';
                } else {
                    $options[] = "'".$option."'";
                }
            }
            
            $options = implode(", ", $options);

            $relationStub = str_replace('{{name}}', $relation['name'], $relationStub);
            $relationStub = str_replace('{{options}}', $options, $relationStub);
            $relationStub = str_replace('{{ucfirst_type}}', ucfirst($relation['type']), $relationStub);
            $relationStub = str_replace('{{type}}', $relation['type'], $relationStub);

            $textRelations[] = $relationStub;
        }

        $stub = str_replace('{{relations}}', implode(PHP_EOL . PHP_EOL, $textRelations), $stub);
    }

    /**
     * Get an array in text format.
     *
     * @param array $array
     * @return string
     */
    protected function getArrayAsText($array)
    {
        $text = var_export($array, true);

        $text = preg_replace('/[ ]{2}/', "    ", $text);
        $text = preg_replace("/\=\>[ \n    ]+array[ ]+\(/", '=> array(', $text);
        return $text = preg_replace("/\n/", "\n    ", $text);
    }

}