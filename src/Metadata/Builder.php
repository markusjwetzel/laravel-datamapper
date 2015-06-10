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
     * The config of the datamapper package.
     *
     * @var array
     */
    protected $config;

    /**
     * Create a new metadata builder instance.
     *
     * @param \Doctrine\Common\Annotations\AnnotationReader $reader
     * @param \Illuminate\Filesystem\ClassFinder $finder
     * @param \Wetzel\Datamapper\Metadata\Validator $validator
     * @param array $config
     * @return void
     */
    public function __construct(AnnotationReader $reader, ClassFinder $finder, MetadataValidator $validator, $config)
    {
        $this->reader = $reader;
        $this->finder = $finder;
        $this->validator = $validator;
        $this->config = $config;
    }

    /**
     * Get all classes for a namespace.
     *
     * @param string namespace
     * @return array
     */
    public function getClassesFromNamespace($namespace=null)
    {
        $base_namespace = '';

        if ( ! $namespace) {
            $base_namespace = str_replace('\\', '/', $this->config['base_namespace']) ?: $this->getAppNamespace();
        }

        $path = $this->stripNamespace($base_namespace, $this->getAppNamespace());

        $directory = app_path() . $path;
        dd($directory);

        return dd($this->finder->findClasses($directory));
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

        // validate pivot tables
        $this->validator->validatePivotTables($metadataArray);

        // generate morphable classes
        $this->generateMorphableClasses($metadataArray);

        return $metadataArray;
    }

    /**
     * Parse a class.
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
            'morphClass' => $this->generateMorphClass($class),
            'table' => new TableDefinition([
                'name' => $this->generateTableName($class),
                'columns' => [],
            ]),
        
            'softDeletes' => false,
            'timestamps' => false,
            'versionable' => false,

            'hidden' => [],
            'visible' => [],
            
            'touches' => [],
            'with' => [],

            'columns' => [],
            'attributes' => [],
            'embeddeds' => [],
            'relations' => [],
        ]);

        foreach($classAnnotations as $annotation) {
            // entity
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Entity) {
                if (! empty($annotation->morphClass)) {
                    $metadata['morphClass'] = $annotation->morphClass;
                }
            }

            // softdeletes
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\SoftDeletes) {
                $metadata['softDeletes'] = true;
            }

            // table name
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Table) {
                $metadata['table']['name'] = $annotation->value;
            }

            // timestamps
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Timestamps) {
                $metadata['timestamps'] = true;
            }

            // versioned
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Versionable) {
                $metadata['versionable'] = true;
            }

            // hidden
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Hidden) {
                $metadata['hidden'] = $annotation->value;
            }

            // visible
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Visible) {
                $metadata['visible'] = $annotation->value;
            }

            // touches
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Touches) {
                $metadata['touches'] = $annotation->value;
            }

            // with
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\With) {
                $metadata['with'] = $annotation->value;
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
                if ($annotation instanceof \Wetzel\Datamapper\Annotations\Attribute) {
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
                if ($annotation instanceof \Wetzel\Datamapper\Annotations\Relation) {
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

        // lead back morphedByMany to inverse morphToMany
        if ($annotation->type == 'morphedByMany') {
            $annotation->type = 'morphToMany';
            $annotation->inverse = true;
        }

        // create extra columns for belongsTo
        if ($annotation->type == 'belongsTo') {
            $this->generateBelongsToColumns($name, $annotation, $metadata);
        }
        
        // create extra columns for morphTo
        if ($annotation->type == 'morphTo') {
            $this->generateMorphToColumns($name, $annotation, $metadata);
        }

        $pivotTable = null;

        // create pivot table for belongsToMany
        if ($annotation->type == 'belongsToMany') {
            $pivotTable = $this->generateBelongsToManyPivotTable($name, $annotation, $metadata);
        }

        // create pivot table for morphToMany
        if ($annotation->type == 'morphToMany') {
            $pivotTable = $this->generateMorphToManyPivotTable($name, $annotation, $metadata);
        }

        // add relation
        return new RelationDefinition([
            'name' => $name,
            'type' => $annotation->type,
            'targetEntity' => $annotation->targetEntity,
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
            $options['foreignKey'] = $annotation->foreignKey ?: $this->generateKey($annotation->targetEntity);
            $options['otherKey'] = $annotation->otherKey ?: 'id';
            $options['relation'] = $annotation->relation;
        }

        // belongsToMany relation
        if($annotation->type == 'belongsToMany') {
            $options['table'] = $annotation->table ?: $this->generatePivotTablename($metadata['class'], $annotation->targetEntity, $annotation->inverse);
            $options['foreignKey'] = $annotation->foreignKey ?: $this->generateKey($annotation->targetEntity);
            $options['otherKey'] = $annotation->otherKey ?: $this->generateKey($metadata['class']);
            $options['relation'] = $annotation->relation;
        }

        // hasMany relation
        if($annotation->type == 'hasMany') {
            $options['foreignKey'] = $annotation->foreignKey ?: $this->generateKey($metadata['class']);
            $options['localKey'] = $annotation->localKey ?: 'id';
        }

        // hasManyThrough relation
        if($annotation->type == 'hasManyThrough') {
            $options['throughEntity'] = $annotation->throughEntity;
            $options['firstKey'] = $annotation->firstKey ?: $this->generateKey($metadata['class']);
            $options['secondKey'] = $annotation->secondKey ?: $this->generateKey($annotation->throughEntity);
        }

        // hasOne relation
        if($annotation->type == 'hasOne') {
            $options['foreignKey'] = $annotation->foreignKey ?: $this->generateKey($metadata['class']);
            $options['localKey'] = $annotation->localKey ?: 'id';
        }

        // morphMany relation
        if($annotation->type == 'morphMany') {
            $options['morphName'] = $annotation->morphName ?: $name;
            $options['morphType'] = $annotation->morphType;
            $options['morphId'] = $annotation->morphId;
            $options['localKey'] = $annotation->localKey ?: 'id';
        }

        // morphOne relation
        if($annotation->type == 'morphOne') {
            $options['morphName'] = $annotation->morphName ?: $name;
            $options['morphType'] = $annotation->morphType;
            $options['morphId'] = $annotation->morphId;
            $options['localKey'] = $annotation->localKey ?: 'id';
        }

        // morphTo relation
        if($annotation->type == 'morphTo') {
            $options['morphName'] = $annotation->morphName ?: $name;
            $options['morphType'] = $annotation->morphType;
            $options['morphId'] = $annotation->morphId;
        }

        // morphToMany relation
        if($annotation->type == 'morphToMany') {
            $options['morphName'] = $annotation->morphName ?: $name;
            $options['table'] = $annotation->table ?: $this->generatePivotTablename($metadata['class'], $annotation->targetEntity, $annotation->inverse, $annotation->morphName);
            if ($annotation->inverse) {
                $options['foreignKey'] = $annotation->morphName.'_id';
                $options['otherKey'] = $annotation->foreignKey ?: $this->generateKey($metadata['class']);
            } else {
                $options['foreignKey'] = $annotation->foreignKey ?: $this->generateKey($annotation->targetEntity);
                $options['otherKey'] = $annotation->morphName.'_id';
            }
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
        $otherKey = $annotation->otherKey ?: $this->generateKey($annotation->targetEntity);

        $metadata['table']['columns'][$otherKey] = new ColumnDefinition([
            'name' => $otherKey,
            'type' => 'integer',
            'nullable' => false,
            'default' => false,
            'primary' => false,
            'unique' => false,
            'index' => false,
            'options' => [
                'unsigned' => true
            ]
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
        $morphName = ( ! empty($annotation->morphName))
            ? $annotation->morphName
            : $name;

        $morphId = ( ! empty($annotation->morphId))
            ? $annotation->morphId
            : $morphName.'_id';

        $morphType = ( ! empty($annotation->morphType))
            ? $annotation->morphType
            : $morphName.'_type';

        $metadata['table']['columns'][$morphId] = new ColumnDefinition([
            'name' => $morphId,
            'type' => 'integer',
            'nullable' => false,
            'default' => false,
            'primary' => false,
            'unique' => false,
            'index' => false,
            'options' => [
                'unsigned' => true
            ]
        ]);

        $metadata['table']['columns'][$morphType] = new ColumnDefinition([
            'name' => $morphType,
            'type' => 'string',
            'nullable' => false,
            'default' => false,
            'primary' => false,
            'unique' => false,
            'index' => false,
            'options' => []
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
        $tableName = ($annotation->table)
            ? $annotation->table
            : $this->generatePivotTablename($metadata['class'], $annotation->targetEntity, $annotation->inverse);

        $foreignKey = $annotation->foreignKey ?: $this->generateKey($metadata['class']);

        $otherKey = $annotation->otherKey ?: $this->generateKey($annotation->targetEntity);

        return new TableDefinition([
            'name' => $tableName,
            'columns' => [
                'id' => new ColumnDefinition([
                    'name' => 'id',
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => true,
                    'unique' => false,
                    'index' => false,
                    'options' => [
                        'autoIncrement' => true,
                        'unsigned' => true
                    ]
                ]),
                $foreignKey => new ColumnDefinition([
                    'name' => $foreignKey,
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => false,
                    'unique' => false,
                    'index' => false,
                    'options' => [
                        'unsigned' => true
                    ]
                ]),
                $otherKey => new ColumnDefinition([
                    'name' => $otherKey,
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => false,
                    'unique' => false,
                    'index' => false,
                    'options' => [
                        'unsigned' => true
                    ]
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
        $morphName = $annotation->morphName;

        $tableName = ($annotation->table)
            ? $annotation->table
            : $this->generatePivotTablename($metadata['class'], $annotation->targetEntity, $annotation->inverse, $morphName);

        $foreignKey = $annotation->foreignKey ?: $this->generateKey($annotation->inverse ? $metadata['class'] : $annotation->targetEntity);

        $morphId = ( ! empty($annotation->otherKey))
            ? $annotation->otherKey
            : $morphName.'_id';

        $morphType = $morphName.'_type';

        return new TableDefinition([
            'name' => $tableName,
            'columns' => [
                'id' => new ColumnDefinition([
                    'name' => 'id',
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => true,
                    'unique' => false,
                    'index' => false,
                    'options' => [
                        'autoIncrement' => true,
                        'unsigned' => true
                    ]
                ]),
                $foreignKey => new ColumnDefinition([
                    'name' => $foreignKey,
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => false,
                    'unique' => false,
                    'index' => false,
                    'options' => [
                        'unsigned' => true
                    ]
                ]),
                $morphId => new ColumnDefinition([
                    'name' => $morphId,
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => false,
                    'unique' => false,
                    'index' => false,
                    'options' => [
                        'unsigned' => true
                    ]
                ]),
                $morphType => new ColumnDefinition([
                    'name' => $morphType,
                    'type' => 'string',
                    'nullable' => false,
                    'default' => false,
                    'primary' => false,
                    'unique' => false,
                    'index' => false,
                    'options' => []
                ]),
            ]
        ]);
    }

    /**
     * Generate array of morphable classes for a morphTo or morphToMany relation.
     *
     * @param array $array
     * @return void
     */
    protected function generateMorphableClasses(&$metadataArray)
    {
        foreach($metadataArray as $key => $metadata) {
            foreach($metadata['relations'] as $relationKey => $relationMetadata) {
                // get morphable classes for morphTo relations
                if ($relationMetadata['type'] == 'morphTo') {
                    $metadataArray[$key]['relations'][$relationKey]['options']['morphableClasses']
                        = $this->getMorphableClasses($metadata['class'], $relationMetadata['options']['morphName'], $metadataArray);
                }

                // get morphable classes for morphToMany relations
                if ($relationMetadata['type'] == 'morphToMany' && ! $relationMetadata['options']['inverse']) {
                    $metadataArray[$key]['relations'][$relationKey]['options']['morphableClasses']
                        = $this->getMorphableClasses($metadata['class'], $relationMetadata['options']['morphName'], $metadataArray, true);
                }
            }
        }
    }

    /**
     * Get array of morphable classes for a morphTo or morphToMany relation.
     *
     * @param string $targetEntity
     * @param string $morphName
     * @param array $metadataArray
     * @param boolean $many
     * @return void
     */
    protected function getMorphableClasses($targetEntity, $morphName, $metadataArray, $many=false)
    {
        $morphableClasses = [];

        foreach($metadataArray as $metadata) {
            foreach($metadata['relations'] as $relationMetadata) {
                // check relation type
                if ( ! (( ! $many && $relationMetadata['type'] == 'morphOne')
                    || ( ! $many && $relationMetadata['type'] == 'morphMany')
                    || ($many && $relationMetadata['type'] == 'morphToMany' && $relationMetadata['options']['inverse'])))
                {
                    continue;
                }

                // check target entity and morph name
                if ($relationMetadata['targetEntity'] == $targetEntity
                    && $relationMetadata['options']['morphName'] == $morphName)
                {
                    $morphableClasses[$metadata['morphClass']] = $metadata['class'];
                }
            }
        }

        return $morphableClasses;
    }

    /**
     * Generate a database key based on given key and class.
     *
     * @param string $class
     * @return string
     */
    protected function generateKey($class)
    {
        return snake_case(class_basename($class)).'_id';
    }

    /**
     * Generate the database tablename of a pivot table.
     *
     * @param string $class2
     * @param string $class1
     * @param boolean $inverse
     * @param string $morph
     * @return string
     */
    protected function generatePivotTablename($class1, $class2, $inverse, $morph=null)
    {
        // datamapper namespace tables
        if ($this->config['namespace_tables']) {
            $base = ($inverse)
                ? $this->generateTableName($class1, true)
                : $this->generateTableName($class2, true);

            $related = ( ! empty($morph))
                ? $morph
                : ( ! empty($inverse)
                    ? snake_case(class_basename($class2))
                    : snake_case(class_basename($class1)));

            return $base . '_' . $related . '_pivot';
        }

        // eloquent default
        $base = snake_case(class_basename($class1));

        $related = snake_case(class_basename($class2));

        $models = array($related, $base);

        sort($models);

        return strtolower(implode('_', $models));
    }

    /**
     * Generate the table associated with the model.
     *
     * @param string $class
     * @return string
     */
    protected function generateTableName($class)
    {
        // datamapper namespace tables
        if ($this->config['namespace_tables']) {
            $className = array_slice(explode('/',str_replace('\\', '/', $class)), 2);

            // delete last entry if entry is equal to the next to last entry
            if (count($className) >= 2 && end($className) == prev($className)) {
                array_pop($className);
            }

            $classBasename = array_pop($className);

            return strtolower(implode('_',array_merge($className, preg_split('/(?<=\\w)(?=[A-Z])/', $classBasename))));
        }

        // eloquent default
        return str_replace('\\', '', snake_case(str_plural(class_basename($class))));
    }

    /**
     * Generate the class name for polymorphic relations.
     *
     * @param string $class
     * @return string
     */
    protected function generateMorphClass($class)
    {
        // datamapper morphclass abbreviations
        if ($this->config['morphclass_abbreviations']) {
            return snake_case(class_basename($class));
        }

        // eloquent default
        return $class;
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

        dd($namespace);

        if (substr($class, 0, strlen($namespace)) == $namespace) {
            return substr($class, strlen($namespace));
        }
        
        return null;
    }

}