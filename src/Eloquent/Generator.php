<?php

namespace ProAI\Datamapper\Eloquent;

use Illuminate\Filesystem\Filesystem;

class Generator
{
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
     * Model stubs.
     *
     * @var array
     */
    protected $stubs;

    /**
     * Constructor.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     * @param string $path
     * @return void
     */
    public function __construct(Filesystem $files, $path)
    {
        $this->files = $files;
        $this->path = $path;

        $this->stubs['model'] = $this->files->get(__DIR__ . '/../../stubs/model.stub');
        $this->stubs['relation'] = $this->files->get(__DIR__ . '/../../stubs/model-relation.stub');
        $this->stubs['morph_extension'] = $this->files->get(__DIR__ . '/../../stubs/model-morph-extension.stub');
    }

    /**
     * Generate model from metadata.
     *
     * @param array $metadata
     * @param boolean $saveMode
     * @return void
     */
    public function generate($metadata, $saveMode=false)
    {
        // clean or make (if not exists) model storage directory
        if (! $this->files->exists($this->path)) {
            $this->files->makeDirectory($this->path);
        }

        // clear existing models if save mode is off
        if (! $saveMode) {
            $this->clean();
        }

        // create models
        foreach ($metadata as $entityMetadata) {
            $this->generateModel($entityMetadata);
        }

        // create .gitignore
        $this->files->put($this->path . '/.gitignore', '*' . PHP_EOL . '!.gitignore');

        // create json file for metadata
        $contents = json_encode($metadata, JSON_PRETTY_PRINT);
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
     * @param array $entityMetadata
     * @return void
     */
    public function generateModel($entityMetadata)
    {
        $stub = $this->stubs['model'];

        // header
        $this->replaceNamespace(get_mapped_model_namespace(), $stub);
        $this->replaceClass(class_basename(get_mapped_model($entityMetadata['class'], false)), $stub);
        $this->replaceMappedClass($entityMetadata['class'], $stub);

        // softDeletes trait
        $this->replaceSoftDeletes($entityMetadata['softDeletes'], $stub);

        // versionable trait
        $this->replaceVersionable($entityMetadata['versionTable'], $stub);

        // table name
        $this->replaceTable($entityMetadata['table']['name'], $stub);

        // primary key
        list($primaryKey, $incrementing) = $this->getPrimaryKey($entityMetadata);
        $this->replacePrimaryKey($primaryKey, $stub);
        $this->replaceIncrementing($incrementing, $stub);

        // timestamps
        $this->replaceTimestamps($entityMetadata['timestamps'], $stub);

        // misc
        $this->replaceTouches($entityMetadata['touches'], $stub);
        $this->replaceWith($entityMetadata['with'], $stub);
        $this->replaceVersioned($entityMetadata['versionTable'], $stub);
        $this->replaceMorphClass($entityMetadata['morphClass'], $stub);

        // mapping data
        $mapping = $this->generateMappingData($entityMetadata);
        $this->replaceMapping($mapping, $stub);
        
        // relations
        $this->replaceRelations($entityMetadata['relations'], $stub);

        $this->files->put($this->path . '/' . get_mapped_model_hash($entityMetadata['class']), $stub);
    }

    /**
     * Get primary key and auto increment value.
     *
     * @param  array  $entityMetadata
     * @return array
     */
    protected function getPrimaryKey($entityMetadata)
    {
        $primaryKey = 'id';
        $incrementing = true;

        foreach ($entityMetadata['table']['columns'] as $column) {
            if (! empty($column['primary'])) {
                $primaryKey = $column['name'];
                $incrementing = (! empty($column['options']['autoIncrement']));
            }
        }

        return [$primaryKey, $incrementing];
    }

    /**
     * Generate mapping array.
     *
     * @param  array  $entityMetadata
     * @return array
     */
    protected function generateMappingData($entityMetadata)
    {
        $attributes = [];
        foreach ($entityMetadata['attributes'] as $attributeMetadata) {
            $attributes[$attributeMetadata['name']] = $attributeMetadata['columnName'];
        }

        $embeddeds = [];
        foreach ($entityMetadata['embeddeds'] as $embeddedMetadata) {
            $embedded = [];
            $embedded['class'] = $embeddedMetadata['class'];
            $embeddedAttributes = [];
            foreach ($embeddedMetadata['attributes'] as $attributeMetadata) {
                $embeddedAttributes[$attributeMetadata['name']] = $attributeMetadata['columnName'];
            }
            $embedded['attributes'] = $embeddedAttributes;
            $embeddeds[$embeddedMetadata['name']] = $embedded;
        }

        $relations = [];
        foreach ($entityMetadata['relations'] as $relationMetadata) {
            $relation = [];
            
            $relation['type'] = $relationMetadata['type'];
            if ($relation['type'] == 'belongsToMany' || $relation['type'] == 'morphToMany') {
                $relation['inverse'] = (! empty($relationMetadata['options']['inverse']));
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
     * Replace softDeletes.
     *
     * @param boolean $option
     * @param string $stub
     * @return void
     */
    protected function replaceSoftDeletes($option, &$stub)
    {
        $stub = str_replace('{{softDeletes}}', $option ? 'use \ProAI\Versioning\SoftDeletes;' . PHP_EOL . PHP_EOL . '    ' : '', $stub);
    }
    
    /**
     * Replace versionable.
     *
     * @param boolean $option
     * @param string $stub
     * @return void
     */
    protected function replaceVersionable($versionTable, &$stub)
    {
        $option = (! empty($versionTable)) ? true : false;
        $stub = str_replace('{{versionable}}', $option ? 'use \ProAI\Versioning\Versionable;' . PHP_EOL . PHP_EOL . '    ' : '', $stub);
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
     * Replace versioned.
     *
     * @param mixed $versionTable
     * @param string $stub
     * @return void
     */
    protected function replaceVersioned($versionTable, &$stub)
    {
        if (! $versionTable) {
            $stub = str_replace('{{versioned}}', $this->getArrayAsText([]), $stub);
            return;
        }

        $versioned = [];
        foreach ($versionTable['columns'] as $column) {
            if (! $column['primary'] || $column['name'] == 'version') {
                $versioned[] = $column['name'];
            }
        }
        $stub = str_replace('{{versioned}}', $this->getArrayAsText($versioned), $stub);
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

        foreach ($relations as $key => $relation) {
            $relationStub = $this->stubs['relation'];

            // generate options array
            $options = [];

            if ($relation['type'] != 'morphTo') {
                $options[] = "'" . get_mapped_model($relation['relatedEntity'], false)."'";
            }

            foreach ($relation['options'] as $name => $option) {
                if ($option === null) {
                    $options[] = 'null';
                } elseif ($option === true) {
                    $options[] = 'true';
                } elseif ($option === false) {
                    $options[] = 'false';
                } else {
                    if ($name == 'throughEntity') {
                        $options[] = "'".get_mapped_model($option)."'";
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
                || ($relation['type'] == 'morphToMany' && ! $relation['options']['inverse'])) {
                $morphStub = $this->stubs['morph_extension'];

                $morphableClasses = [];
                foreach ($relation['options']['morphableClasses'] as $key => $name) {
                    $morphableClasses[$key] = get_mapped_model($name, false);
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
     * Get an array in text format.
     *
     * @param array $array
     * @return string
     */
    protected function getArrayAsText($array, $intendBy=1)
    {
        $intention = '';
        for ($i=0; $i<$intendBy; $i++) {
            $intention .= '    ';
        }

        $text = var_export($array, true);

        $text = preg_replace('/[ ]{2}/', '    ', $text);
        $text = preg_replace("/\=\>[ \n    ]+array[ ]+\(/", '=> array(', $text);
        return $text = preg_replace("/\n/", "\n".$intention, $text);
    }
}
