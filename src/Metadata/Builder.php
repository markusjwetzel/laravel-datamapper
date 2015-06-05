<?php namespace Wetzel\Datamapper\Metadata;

use ReflectionClass;
use InvalidArgumentException;
use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Filesystem\ClassFinder;

use Doctrine\Common\Annotations\AnnotationReader;

use Wetzel\Datamapper\Metadata\Validator as MetadataValidator;
use Wetzel\Datamapper\Metadata\Definitions\Entity as EntityDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Attribute as AttributeDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Column as ColumnDefinition;
use Wetzel\Datamapper\Metadata\Definitions\EmbeddedClass as EmbeddedClassDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Relation as RelationDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Table as TableDefinition;

class Builder {

    use AppNamespaceDetectorTrait;

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
     * The metadata validator instance.
     *
     * @var \Wetzel\Datamapper\Metadata\Validator
     */
    protected $validator;

    /**
     * Create a new metadata builder instance.
     *
     * @param  \Doctrine\Common\Annotations\AnnotationReader  $reader
     * @param  \Illuminate\Filesystem\ClassFinder  $finder
     * @param  \Wetzel\Datamapper\Metadata\Validator  $validator
     * @return void
     */
    public function __construct(AnnotationReader $reader, ClassFinder $finder, MetadataValidator $validator)
    {
        $this->reader = $reader;
        $this->finder = $finder;
        $this->validator = $validator;
    }

    /**
     * Build metadata from all entity classes.
     *
     * @param array $classes
     * @return array
     */
    public function build($classes)
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
     * Get all classes for a namespace.
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
     * Parse an entity class.
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
        $metadata = new EntityDefinition([
            'class' => $class,
            'table' => new TableDefinition([
                'name' => $this->generateTablename($class),
            ]),
        ]);

        foreach($classAnnotations as $annotation) {
            // softdeletes
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\SoftDeletes) {
                $metadata['softDeletes'] = true;
            }

            // table name
            elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Table) {
                $metadata['table']['name'] = $annotation->name;
            }

            // timestamps
            elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Timestamps) {
                $metadata['timestamps'] = true;
            }

            // versioned
            elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Versionable) {
                $metadata['versionable'] = true;
            }

            // hidden
            elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Hidden) {
                $metadata['hidden'] = $annotation->attributes;
            }

            // visible
            elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Visible) {
                $metadata['visible'] = $annotation->attributes;
            }

            // touches
            elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Touches) {
                $metadata['touches'] = $annotation->relations;
            }
        }

        // scan property annotations
        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            $propertyAnnotations = $this->reader->getPropertyAnnotations($reflectionProperty);

            foreach($propertyAnnotations as $annotation) {
                // property is embedded class
                if ($annotation instanceof \Wetzel\Datamapper\Annotations\Embedded) {
                    $metadata['embeddeds'][$name] = $this->parseEmbeddedClass($name, $annotation, $metadata);
                }

                // property is attribute
                elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Attribute) {
                    // try to find @Id annotation -> set primary key
                    foreach($propertyAnnotations as $subAnnotation) {
                        if ($subAnnotation instanceof \Wetzel\Datamapper\Annotations\Id) {
                            $annotation->primary = true;
                        }
                    }
                    // set attribute data
                    $metadata['attributes'][$name] = $this->parseAttribute($name, $annotation);
                    $metadata['table']['columns'][$name] = $this->parseColumn($name, $annotation);
                }

                // property is relationship
                elseif ($annotation instanceof \Wetzel\Datamapper\Annotations\Relation) {
                    $metadata['relations'][$name] = $this->parseRelation($name, $annotation, $metadata);
                }
            }
        }

        // check primary key
        $this->validator->validatePrimaryKey($metadata['table']);

        return $metadata;
    }

    /**
     * Parse an embedded class.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\EmbeddedClass
     */
    protected function parseEmbeddedClass($name, $annotation, &$metadata)
    {
        $embeddedClass = $annotation->class;
        $embeddedName = $name;
        $reflectionClass = new ReflectionClass($embeddedClass);

        // scan class annotations
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        // check if class is embedded class
        if ( ! $this->reader->getClassAnnotation($reflectionClass, 'Wetzel\Datamapper\Annotations\Embeddable')) {
            throw new InvalidArgumentException('Embedded class '.$embeddedClass.' has no @Embeddable annotation.');
        }

        // scan property annotations
        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            $propertyAnnotations = $this->reader->getPropertyAnnotations($reflectionProperty);
            
            $attributes = [];

            foreach($propertyAnnotations as $annotation) {
                // property is attribute
                if ($annotation instanceof \Wetzel\Datamapper\Annotations\Attribute) {
                    $attributes[$name] = $this->parseAttribute($name, $annotation);
                    $metadata['table']['columns'][$name] = $this->parseColumn($name, $annotation);
                }
            }

            return new EmbeddedClassDefinition([
                'name' => $embeddedName,
                'class' => $embeddedClass,
                'attributes' => $attributes,
            ]);
        }
    }

    /**
     * Parse an attribute.
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
     * Parse a column.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @return \Wetzel\Datamapper\Metadata\Definitions\Column
     */
    protected function parseColumn($name, $annotation)
    {
        // check if column type is valid
        $this->validator->validateColumnType($annotation->type);

        // add column
        return new ColumnDefinition([
            'name' => $name,
            'type' => $annotation->type,
            'nullable' => $annotation->nullable,
            'default' => $annotation->default,
            'primary' => $annotation->primary,
            'unique' => $annotation->unique,
            'index' => $annotation->index,
            'options' => $this->generateAttributeOptionsArray($annotation)
        ]);
    }

    /**
     * Generate an options array for an attribute.
     *
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @return array
     */
    protected function generateAttributeOptionsArray($annotation)
    {
        $options = [];

        // length option
        if($annotation->type == 'string' || $annotation->type == 'char') {
            $options['length'] = $annotation->length;
        }

        // unsigned and autoIncrement option
        if($annotation->type == 'smallInteger' || $annotation->type == 'integer' || $annotation->type == 'bigInteger') {
            $options['unsigned'] = $annotation->unsigned;
            $options['autoIncrement'] = $annotation->autoIncrement;
        }

        // scale and precision option
        if($annotation->type == 'decimal') {
            $options['scale'] = $annotation->scale;
            $options['precision'] = $annotation->precision;
        }

        return $options;
    }

    /**
     * Parse a relationship.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\Relation
     */
    protected function parseRelation($name, $annotation, &$metadata)
    {
        // check if relation type is valid
        $this->validator->validateRelationType($annotation->type);

        // add columns for relationships
        if ($annotation->type == 'belongsTo') {
            // create extra columns for belongsTo
            $this->generateBelongsToColumns($name, $annotation, $metadata);
        } elseif ($annotation->type == 'morphTo') {
            // create extra columns for morphTo
            $this->generateMorphToColumns($name, $annotation, $metadata);
        }

        // add pivot tables for relationships
        if ($annotation->type == 'belongsToMany') {
            // create pivot table for belongsToMany
            $pivotTable = $this->generateBelongsToManyPivotTable($name, $annotation, $metadata);
        } elseif ($annotation->type == 'morphedByMany' || $annotation->type == 'morphToMany') {
            // create pivot table for morphToMany
            $pivotTable = $this->generateMorphToManyPivotTable($name, $annotation, $metadata);
        } else {
            $pivotTable = null;
        }

        // add relation
        return new RelationDefinition([
            'name' => $name,
            'type' => $annotation->type,
            'relatedClass' => $annotation->related,
            'pivotTable' => $pivotTable,
            'options' => $this->generateRelationOptionsArray($name, $annotation, $metadata)
        ]);
    }

    /**
     * Generate an options array for a relation.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return array
     */
    protected function generateRelationOptionsArray($name, $annotation, &$metadata)
    {
        $options = [];

        // belongsTo relation
        if($annotation->type == 'belongsTo') {
            $options['foreignKey'] = $this->generateKey($annotation->foreignKey, $annotation->related);
            $options['otherKey'] = $annotation->otherKey;
            $options['relation'] = $annotation->relation;
        }

        // belongsToMany relation
        if($annotation->type == 'belongsToMany') {
            $options['table'] = $this->generateTablename($annotation->table, $metadata['table'], $annotation->related);
            $options['foreignKey'] = $this->generateKey($annotation->foreignKey, $annotation->related);
            $options['otherKey'] = $annotation->otherKey;
            $options['relation'] = $annotation->relation;
        }

        // hasMany relation
        if($annotation->type == 'hasMany') {
            $options['foreignKey'] = $this->generateKey($annotation->foreignKey, $metadata['class']);
            $options['localKey'] = $annotation->localKey;
        }

        // hasManyThrough relation
        if($annotation->type == 'hasManyThrough') {
            $options['through'] = $annotation->through;
            $options['firstKey'] = $annotation->firstKey;
            $options['secondKey'] = $annotation->secondKey;
        }

        // hasOne relation
        if($annotation->type == 'hasOne') {
            $options['foreignKey'] = $this->generateKey($annotation->foreignKey, $metadata['class']);
            $options['localKey'] = $annotation->localKey;
        }

        // morphToMany relation
        if($annotation->type == 'morphedByMany') {
            $options['name'] = $annotation->name;
            $options['table'] = $this->generatePivotTablename($annotation->table, $metadata['table'], null, $morphName);
            $options['foreignKey'] = $annotation->foreignKey;
            $options['otherKey'] = $annotation->otherKey;
        }

        // morphMany relation
        if($annotation->type == 'morphMany') {
            $options['name'] = $annotation->name;
            $options['morphType'] = $annotation->morphType;
            $options['morphId'] = $annotation->morphId;
            $options['localKey'] = $annotation->localKey;
        }

        // morphOne relation
        if($annotation->type == 'morphOne') {
            $options['name'] = $annotation->name;
            $options['morphType'] = $annotation->morphType;
            $options['morphId'] = $annotation->morphId;
            $options['localKey'] = $annotation->localKey;
        }

        // morphTo relation
        if($annotation->type == 'morphTo') {
            $options['name'] = $annotation->name;
            $options['morphType'] = $annotation->morphType;
            $options['morphId'] = $annotation->morphId;
        }

        // morphToMany relation
        if($annotation->type == 'morphToMany') {
            $options['name'] = $annotation->name;
            $options['table'] = $this->generatePivotTablename($annotation->table, $metadata['table'], null, $morphName);
            $options['foreignKey'] = $annotation->foreignKey;
            $options['otherKey'] = $annotation->otherKey;
            $options['inverse'] = $annotation->inverse;
        }

        return $options;
    }

    /**
     * Generate extra columns for a belongsTo relation.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return void
     */
    protected function generateBelongsToColumns($name, $annotation, &$metadata)
    {
        $otherKey = $this->generateKey($annotation->otherKey, $annotation->related);

        $metadata['table']['columns'][$otherKey] = new ColumnDefinition([
            'name' => $otherKey,
            'type' => 'integer',
            'unsigned' => true,
        ]);
    }

    /**
     * Generate extra columns for a morphTo relation.
     *
     * @param array $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return void
     */
    protected function generateMorphToColumns($name, $annotation, &$metadata)
    {
        $morphName = ( ! empty($annotation->name))
            ? $annotation->name
            : $name;

        $morphId = ( ! empty($annotation->id))
            ? $annotation->id
            : $morphName.'_id';

        $morphType = ( ! empty($annotation->type))
            ? $annotation->type
            : $morphName.'_type';

        $metadata['table']['columns'][$morphId] = new ColumnDefinition([
            'name' => $morphId,
            'type' => 'integer',
            'unsigned' => true,
        ]);

        $metadata['table']['columns'][$morphType] = new ColumnDefinition([
            'name' => $morphType,
            'type' => 'string',
        ]);
    }
    
    /**
     * Generate pivot table for a belongsToMany relation.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\Table
     */
    protected function generateBelongsToManyPivotTable($name, $annotation, &$metadata)
    {
        $tableName = $this->generatePivotTablename($annotation->table, $metadata['table']['name'], $annotation->related);

        $foreignKey = $this->generateKey($annotation->foreignKey, $metadata['class']);

        $otherKey = $this->generateKey($annotation->otherKey, $annotation->related);

        return new TableDefinition([
            'name' => $tableName,
            'columns' => [
                $foreignKey => new ColumnDefinition([
                    'name' => $foreignKey,
                    'type' => 'integer',
                    'unsigned' => true,
                ]),
                $otherKey => new ColumnDefinition([
                    'name' => $otherKey,
                    'type' => 'integer',
                    'unsigned' => true,
                ]),
            ]
        ]);
    }
    
    /**
     * Generate pivot table for a morphToMany relation.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $metadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\Table
     */
    protected function generateMorphToManyPivotTable($name, $annotation, &$metadata)
    {
        $morphName = ( ! empty($annotation->name))
            ? $annotation->name
            : $name;

        $tableName = $this->generatePivotTablename($annotation->table, $metadata['table']['name'], null, $morphName);

        $foreignKey = $this->generateKey($annotation->foreignKey, $metadata['class']);

        $morphId = ( ! empty($annotation->otherKey))
            ? $annotation->otherKey
            : $morphName.'_id';

        $morphType = $morphName.'_type';

        return new TableDefinition([
            'name' => $tableName,
            'columns' => [
                $foreignKey => new ColumnDefinition([
                    'name' => $foreignKey,
                    'type' => 'integer',
                    'unsigned' => true,
                ]),
                $morphId => new ColumnDefinition([
                    'name' => $morphId,
                    'type' => 'integer',
                    'unsigned' => true,
                ]),
                $morphType => new ColumnDefinition([
                    'name' => $morphType,
                    'type' => 'string',
                ]),
            ]
        ]);
    }

    /**
     * Generate a database key based on given key and class.
     *
     * @param string $key
     * @param string $class
     * @return string
     */
    protected function generateKey($key, $class)
    {
        $generatedKey = ( ! empty($key))
            ? $key
            : $this->getClassWithoutNamespace($class, true).'_id';

        // uncamelize
        $generatedKey = strtolower(implode('_',preg_split('/(?<=\\w)(?=[A-Z])/', $generatedKey)));

        return $generatedKey;
    }

    /**
     * Generate the database tablename of a pivot table.
     *
     * @param string $table
     * @param string $prefix
     * @param string $class
     * @param string $morph
     * @return string
     */
    protected function generatePivotTablename($table, $prefix, $class=null, $morph=null)
    {
        $generatedPivotTablename = ( ! empty($table))
            ? $table
            : $prefix.'_'.($morph ?: $this->getClassWithoutNamespace($class, true)).'_pivot';

        // uncamelize
        $generatedPivotTablename = strtolower(implode('_',preg_split('/(?<=\\w)(?=[A-Z])/', $generatedPivotTablename)));

        return $generatedPivotTablename;
    }

    /**
     * Generate table name.
     *
     * @param string $class
     * @return string
     */
    protected function generateTablename($class)
    {
        $className = array_slice(explode('/',str_replace('\\', '/', $class)), 2);

        // delete last entry if entry is equal to the next to last entry
        if (count($className) >= 2 && end($className) == prev($className)) {
            array_pop($className);
        }

        $classBasename = array_pop($className);

        return strtolower(implode('_',array_merge($className, preg_split('/(?<=\\w)(?=[A-Z])/', $classBasename))));
    }

    /**
     * Strip given namespace from class.
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
     * Get class name.
     *
     * @param string|object $class
     * @param boolean $lcfirst
     * @return string
     */
    protected function getClassWithoutNamespace($class, $lcfirst=false)
    {
        $class = (is_object($class)) ? get_class($class) : $class;
        
        $items = explode('\\', $class);

        $class = array_pop($items);

        if ($lcfirst) {
            return lcfirst($class);
        } else {
            return $class;
        }
    }

}