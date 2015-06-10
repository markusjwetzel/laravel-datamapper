<?php namespace Wetzel\Datamapper\Eloquent;

use Illuminate\Filesystem\Filesystem;

class Generator {

    /**
     * The filesystem instance.
     * 
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Path to model storage directory.
     * 
     * @var array
     */
    protected $path;

    /**
     * Namespace of mapped models.
     * 
     * @var array
     */
    protected $namespace;

    /**
     * Model stubs.
     * 
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
        $this->path = $storagePath . '/framework/entities';
        $this->namespace = 'Wetzel\Datamapper\Cache';

        $this->stubs['model'] = $this->files->get(__DIR__ . '/../../stubs/model.stub');
        $this->stubs['relation'] = $this->files->get(__DIR__ . '/../../stubs/model-relation.stub');
        $this->stubs['morph_extension'] = $this->files->get(__DIR__ . '/../../stubs/model-morph-extension.stub');
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
        if ( ! $this->files->exists($this->path)) {
            $this->files->makeDirectory($this->path);
        }

        // clear existing models if save mode is off
        if ( ! $saveMode) {
            $this->clean();
        }

        // create models
        foreach($metadataArray as $metadata) {
            $this->generateModel($metadata);
        }

        // create .gitignore
        $this->files->put($this->path . '/.gitignore', '*' . PHP_EOL . '!.gitignore');

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
        if ($this->files->exists($this->path)) {
            $this->files->cleanDirectory($this->path);
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

        // header
        $this->replaceNamespace($this->namespace, $stub);
        $this->replaceClass(class_basename($this->getMappedClass($metadata['class'])), $stub);
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
        $this->replaceWith($metadata['with'], $stub);
        $this->replaceMorphClass($metadata['morphClass'], $stub);

        // mapping data
        $mapping = $this->generateMappingData($metadata);
        $this->replaceMapping($mapping, $stub);
        
        // relations
        $this->replaceRelations($metadata['relations'], $stub);

        $this->files->put($this->path . '/' . $this->getMappedClassHash($metadata['class']), $stub);
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
            if ( ! empty($relationMetadata['targetEntity'])) {
                $relation['targetEntity'] = $relationMetadata['targetEntity'];
                $relation['mappedTargetEntity'] = $this->getMappedClass($relationMetadata['targetEntity']);
            } else {
                $relation['targetEntity'] = null;
                $relation['mappedTargetEntity'] = null;
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
     * Replace with.
     * 
     * @param array $with
     * @param string $stub
     * @return void
     */
    protected function replaceWith($with, &$stub)
    {
        $stub = str_replace('{{with}}', $this->getArrayAsText($with), $stub);
    }

    /**
     * Replace the morph classname for the given stub.
     *
     * @param  string  $name
     * @param  string  $stub
     * @return void
     */
    protected function replaceMorphClass($name, &$stub)
    {
        $stub = str_replace('{{morphClass}}', "'".$name."'", $stub);
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

        foreach($relations as $key => $relation) {
            $relationStub = $this->stubs['relation'];

            // generate options array
            $options = [];

            if ($relation['type'] != 'morphTo') {
                $options[] = "'".$this->getMappedClass($relation['targetEntity'])."'";
            }

            foreach($relation['options'] as $name => $option) {
                if ($option === null) {
                    $options[] = 'null';
                } elseif ($option === true) {
                    $options[] = 'true';
                } elseif ($option === false) {
                    $options[] = 'false';
                } else {
                    if ($name == 'throughEntity') {
                        $options[] = "'".$this->getMappedClass($option)."'";
                    } elseif ($name != 'morphableClasses') {
                        $options[] = "'".$option."'";
                    }
                }
            }
            
            $options = implode(", ", $options);

            $relationStub = str_replace('{{name}}', $relation['name'], $relationStub);
            $relationStub = str_replace('{{options}}', $options, $relationStub);
            $relationStub = str_replace('{{ucfirst_type}}', ucfirst($relation['type']), $relationStub);
            $relationStub = str_replace('{{type}}', $relation['type'], $relationStub);

            $textRelations[] = $relationStub;

            if ($relation['type'] == 'morphTo'
                || ($relation['type'] == 'morphToMany' && ! $relation['options']['inverse']))
            {
                $morphStub = $this->stubs['morph_extension'];

                $morphableClasses = [];
                foreach($relation['options']['morphableClasses'] as $key => $name) {
                    $morphableClasses[$key] = $this->getMappedClass($name);
                }

                $morphStub = str_replace('{{name}}', $relation['name'], $morphStub);
                $morphStub = str_replace('{{morphName}}', ucfirst($relation['options']['morphName']), $morphStub);
                $morphStub = str_replace('{{types}}', $this->getArrayAsText($morphableClasses, 2), $morphStub);

                $textRelations[] = $morphStub;
            }
        }

        $stub = str_replace('{{relations}}', implode(PHP_EOL . PHP_EOL, $textRelations), $stub);
    }

    /**
     * Get mapped class.
     *
     * @param string $class
     * @return string
     */
    protected function getMappedClass($class)
    {
        return $this->namespace . '\Entity' . $this->getMappedClassHash($class);
    }

    /**
     * Get mapped class hash.
     *
     * @param string $class
     * @return string
     */
    protected function getMappedClassHash($class)
    {
        return md5($class);
    }

    /**
     * Get an array in text format.
     *
     * @param array $array
     * @return string
     */
    protected function getArrayAsText($array, $intendBy=1)
    {
        $intention = '';
        for($i=0; $i<$intendBy; $i++) {
            $intention .= '    ';
        }

        $text = var_export($array, true);

        $text = preg_replace('/[ ]{2}/', '    ', $text);
        $text = preg_replace("/\=\>[ \n    ]+array[ ]+\(/", '=> array(', $text);
        return $text = preg_replace("/\n/", "\n".$intention, $text);
    }

}