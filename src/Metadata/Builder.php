<?php namespace Wetzel\Datamapper\Metadata;

use InvalidArgumentException;
use Doctrine\Common\Annotations\AnnotationReader;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Filesystem\ClassFinder;

use Wetzel\Datamapper\Metadata\Definitions\Class as ClassDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Attribute as AttributeDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Column as ColumnDefinition;
use Wetzel\Datamapper\Metadata\Definitions\EmbeddedClass as EmbeddedClassDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Relation as RelationDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Table as TableDefinition;

class Builder {

    use AppNamespaceDetectorTrait;
    
    /**
     * All attribute types supported by Laravel.
     *
     * @var array
     */
    /*protected $attributeTypes = [
        'bigInteger',
        'binary',
        'boolean',
        'char',
        'date',
        'dateTime',
        'decimal',
        'double',
        'enum',
        'float',
        'integer',
        'json',
        'jsonb',
        'longText',
        'mediumInteger',
        'mediumText',
        'smallInteger',
        'tinyInteger',
        'string',
        'text',
        'time',
        'timestamp'
    ];*/

    /**
     * The annotation reader instance.
     *
     * @var \Doctrine\Common\Annotations\AnnotationReader
     */
    protected $reader;

    /**
     * The class finder instance.
     *
     * @var \Illuminate\Filesystem\ClassFinder
     */
    protected $finder;

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \AnnotationReader  $reader
     * @return void
     */
    public function __construct(AnnotationReader $reader, Finder $finder)
    {
        $this->reader = $reader;
        $this->finder = $finder;
    }

    /**
     * Build metadata from all entity classes.
     *
     * @param array $classes
     * @return array
     */
    public function getMetadata($classes)
    {
        $metadataArray = [];

        foreach($classes as $class) {
            // check if class is in app namespace
            if ($this->stripNamespace($class, $this->getAppNamespace())) {
                $metadata = $this->parseClass($class);

                if ($metadata) {
                    $metadataArray[$class] = $metadata;
                }
            }
        }

        return $metadataArray;
    }

    /**
     * Gets all classes for a namespace.
     *
     * @param string namespace
     * @return array
     */
    public function getClassesFromNamespace($namespace=null)
    {
        if ( ! $namespace) {
            $namespace = $this->getAppNamespace();
        }

        $path = str_replace('\\', '/', $this->stripNamespace($namespace, $this->getAppNamespace()));

        $directory = app_path() . $path;

        return $this->finder->findClasses($directory);
    }

    /**
     * Parses a class.
     *
     * @param array $annotations
     * @return array|null
     */
    public function parseClass($class)
    {
        $reflectionClass = new ReflectionClass($class);

        // check if class is entity
        if ($this->reader->getClassAnnotation($reflectionClass, '\Wetzel\Datamapper\Annotations\Entity')) {
            return $this->parseEntity($class);
        } else {
            return null;
        }
    }

    /**
     * Parses an entity class.
     *
     * @param array $class
     * @return array
     */
    public function parseEntity($class)
    {
        $reflectionClass = new ReflectionClass($class);

        // scan class annotations
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        // init class metadata
        $metadata = new ClassDefinition([
            'class' => get_class($class);
        ]);

        foreach($classAnnotations as $annotation) {
            // table name
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Table) {
                $metadata['table']['name'] = $annotation['value'];
            }

            // timestamps
            elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Timestamps) {
                $metadata['timestamps'] = true;
            }

            // softdeletes
            elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\SoftDeletes) {
                $metadata['softdeletes'] = true;
            }

            // versioned
            elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Revisions) {
                $metadata['revisions'] = true;
            }
        }

        // scan property annotations
        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            $propertyAnnotations = $reader->getPropertyAnnotations($reflectionProperty);

            foreach($propertyAnnotations as $annotation) {
                // property is embedded class
                elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Embedded) {
                    $metadata['embeddeds'][$name] = $this->parseEmbedded($name, $annotation, $metadata);
                }

                // property is attribute
                if ($this->stripNamespace($annotation, 'Wetzel\Datamapper\Annotations\Attribute')) {
                    $metadata['attributes'][$name] = $this->parseAttribute($name, $annotation);
                    $metadata['table']['columns'][$name] = $this->parseColumn($name, $annotation);
                }

                // property is relationship
                elseif ($this->stripNamespace($annotation, 'Wetzel\Datamapper\Annotations\Relation')) {
                    $metadata['relations'][$name] = $this->parseRelation($name, $annotation, $metadata);
                }
            }
        }

        return $metadata;
    }

    /**
     * Parses an embedded class.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\EmbeddedClass
     */
    public function parseEmbeddedClass($name, $annotation, &$metadata) {
        $reflectionClass = new ReflectionClass($annotation['class']);

        // scan class annotations
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        // check if class is embedded class
        if ( ! $this->reader->getClassAnnotation($reflectionClass, '\Wetzel\Datamapper\Annotations\Embedded')) {
            throw new InvalidArgumentException('Embedded class '.$annotation['class'].' has no @Embedded annotation.');
        }

        // scan property annotations
        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            $propertyAnnotations = $reader->getPropertyAnnotations($reflectionProperty);
            
            $attributes = [];

            foreach($propertyAnnotations as $annotation) {
                // property is attribute
                if ($annotation instanceof \Wetzel\Datamapper\Annotations\Attribute) {
                    $attributes[$name] = $this->parseAttribute($name, $annotation);
                    $metadata['table']['columns'][$name] = $this->parseColumn($name, $annotation);
                }
            }

            return new EmbeddedClassDefinition([
                'name' => $name,
                'embeddedClass' => $annotation['class'],
                'attributes' => $attributes,
            ]);
        }
    }

    /**
     * Parses an attribute.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @return \Wetzel\Datamapper\Metadata\Definitions\Attribute
     */
    protected function parseAttribute($name, $annotation)
    {
        // add attribute
        return new AttributeDefinition([
            'name' => $name
        ]);
    }

    /**
     * Parses a column.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @return \Wetzel\Datamapper\Metadata\Definitions\Column
     */
    protected function parseColumn($name, $annotation)
    {
        $type = $this->getClassWithoutNamespace($annotation, true);

        // add column
        return new ColumnDefinition([
            'name' => $name,
            'type' => $type,
            'nullable' => $annotation['nullable'],
            'default' => $annotation['default'],
            'unsigned' => $annotation['unsigned'],
            'primary' => $annotation['primary'],
            'unique' => $annotation['unique'],
            'index' => $annotation['index'],
            'options' => array_except($annotation, ['nullable', 'default', 'unsigned', 'primary', 'unique', 'index'],
        ]);
    }

    /**
     * Parses a relationship.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\Relation
     */
    protected function parseRelation($name, $annotation, &$metadata)
    {
        $type = $this->getClassWithoutNamespace($annotation, true);

        if ($type == 'belongsTo') {
            // create extra columns for belongsTo
            $this->generateBelongsToColumns($name, $annotation, $metadata);
        } elseif ($type == 'morphTo') {
            // create extra columns for morphTo
            $this->generateMorphToColumns($name, $annotation, $metadata);
        }

        if ($type == 'belongsToMany') {
            // create pivot table for belongsToMany
            $pivotTable = $this->generateBelongsToManyPivotTable($name, $annotation, $metadata);
        } elseif ($type == 'morphToMany') {
            // create pivot table for morphToMany
            $pivotTable = $this->generateMorphToManyPivotTable($name, $annotation, $metadata);
        } else {
            $pivotTable = null;
        }

        // add relation
        return new RelationDefinition([
            'name' => $name,
            'type' => $type,
            'related' => $annotation['related'],
            'pivotTable' => $pivotTable,
            'options' => array_except($annotation, ['related'],
        ]);
    }

    /**
     * Generates extra columns for a belongsTo relation.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return void
     */
    protected function generateBelongsToColumns($name, $annotation, &$metadata)
    {
        $name = ( ! empty($annotation['otherKey']))
            ? $annotation['otherKey']
            : $this->getClassWithoutNamespace($annotation['related'], true).'_id';

        $this->metadata['table']['columns'][$name] = new ColumnDefinition([
            'name' => $name,
            'type' => 'integer',
            'nullable' => $annotation['nullable'],
            'unsigned' => true,
        ]);
    }

    /**
     * Generates extra columns for a morphTo relation.
     *
     * @param array $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return void
     */
    protected function generateMorphToColumns($name, $annotation, &$metadata)
    {
        $morphName = ( ! empty($annotation['name']))
            ? $annotation['name']
            : $name;

        $morphId = ( ! empty($annotation['id']))
            ? $annotation['id']
            : $morphName.'_id';

        $morphType = ( ! empty($annotation['type']))
            ? $annotation['type']
            : $morphName.'_type';

        $metadata['table']['columns'][$morphId] = new ColumnDefinition([
            'name' => $morphId,
            'type' => 'integer',
            'nullable' => $annotation['nullable'],
            'unsigned' => true,
        ]);

        $metadata['table']['columns'][$morphType] = new ColumnDefinition([
            'name' => $morphType,
            'type' => 'string',
            'nullable' => $annotation['nullable'],
        ]);
    }
    
    /**
     * Generates pivot table for a belongsToMany relation.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\Table
     */
    protected function generateBelongsToManyPivotTable($name, $annotation, &$metadata)
    {
        $tableName = ( ! empty($annotation['table']))
            ? $annotation['table']
            : $this->metadata['table'].'_'.$this->getClassWithoutNamespace($annotation['related'], true).'_pivot';

        $foreignKey = ( ! empty($annotation['foreignKey'])
            ? $annotation['foreignKey']
            : $this->getClassWithoutNamespace($metadata['class'], true).'_id';

        $otherKey = ( ! empty($annotation['otherKey'])
            ? $annotation['otherKey']
            : $this->getClassWithoutNamespace($annotation['related'], true).'_id';

        return new TableDefinition([
            'name' => $tableName,
            'columns' => [
                $foreignKey => new ColumnDefinition([
                    'name' => $foreignKey,
                    'type' => 'integer',
                    'nullable' => $annotation['nullable'],
                    'unsigned' => true,
                ]),
                $otherKey => new ColumnDefinition([
                    'name' => $otherKey,
                    'type' => 'integer',
                    'nullable' => $annotation['nullable'],
                    'unsigned' => true,
                ]),
            ]
        ]);
    }
    
    /**
     * Generates pivot table for a morphToMany relation.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\Table
     */
    protected function generateBelongsToManyPivotTable($name, $annotation, &$metadata)
    {
        $morphName = ( ! empty($annotation['name']))
            ? $annotation['name']
            : $name;

        $tableName = ( ! empty($annotation['table']))
            ? $annotation['table']
            : $this->metadata['table'].'_'.$morphName.'_pivot';

        $foreignKey = ( ! empty($annotation['foreignKey']))
            ? $annotation['foreignKey']
            : $this->getClassWithoutNamespace($metadata['class'], true).'_id';

        $morphId = ( ! empty($annotation['otherKey']))
            ? $annotation['otherKey']
            : $morphName.'_id';

        $morphType = $morphName.'_type';

        return new TableDefinition([
            'name' => $tableName,
            'columns' => [
                $foreignKey => new ColumnDefinition([
                    'name' => $foreignKey,
                    'type' => 'integer',
                    'nullable' => $annotation['nullable'],
                    'unsigned' => true,
                ]),
                $morphId => new ColumnDefinition([
                    'name' => $morphId,
                    'type' => 'integer',
                    'nullable' => $annotation['nullable'],
                    'unsigned' => true,
                ]),
                $morphType => new ColumnDefinition([
                    'name' => $morphType,
                    'type' => 'string',
                    'nullable' => $annotation['nullable'],
                ]),
            ]
        ]);
    }

    /**
     * Gets class name.
     *
     * @param string|object $class
     * @param string $namespace
     * @return string|null
     */
    protected function stripNamespace($class, $namespace)
    {
        $class = (is_object($class)) ? get_class($class) : $class;

        if (substr($class, 0, strlen($namespace)) == $namespace) {
            return substr($class, strlen($namespace));
        } else {
            return null;
        }
    }

    /**
     * Gets class name.
     *
     * @param string|object $class
     * @param boolean $lcfirst
     * @return string
     */
    protected function getClassWithoutNamespace($class, $lcfirst=false)
    {
        $class = (is_object($class)) ? get_class($class) : $class;

        $class = array_pop(explode('\\', $class));

        if ($lcfirst) {
            return lcfirst($class);
        } else {
            return $class;
        }
    }

}