<?php

namespace Wetzel\Datamapper\Metadata;

use ReflectionClass;
use Doctrine\Common\Annotations\AnnotationReader;
use Wetzel\Datamapper\Metadata\EntityValidator;
use Wetzel\Datamapper\Metadata\Definitions\Entity as EntityDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Attribute as AttributeDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Column as ColumnDefinition;
use Wetzel\Datamapper\Metadata\Definitions\EmbeddedClass as EmbeddedClassDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Relation as RelationDefinition;
use Wetzel\Datamapper\Metadata\Definitions\Table as TableDefinition;

class EntityScanner
{
    /**
     * The annotation reader instance.
     *
     * @var \Doctrine\Common\Annotations\AnnotationReader
     */
    protected $reader;

    /**
     * The metadata validator instance.
     *
     * @var \Wetzel\Datamapper\Metadata\Validator
     */
    protected $validator;

    /**
     * Option for using tablesnames by namespace instead of default Eloquent tablenames.
     *
     * @var boolean
     */
    protected $namespaceTablenames = true;

    /**
     * Option for using short morph class names instead of default Eloquent morph classes.
     *
     * @var boolean
     */
    protected $morphClassAbbreviations = true;

    /**
     * Create a new metadata builder instance.
     *
     * @param \Doctrine\Common\Annotations\AnnotationReader $reader
     * @param \Wetzel\Datamapper\Metadata\EntityValidator $validator
     * @param array $config
     * @return void
     */
    public function __construct(AnnotationReader $reader, EntityValidator $validator)
    {
        $this->reader = $reader;
        $this->validator = $validator;
    }

    /**
     * Build metadata from all entity classes.
     *
     * @param array $classes
     * @return array
     */
    public function scan($classes, $namespaceTablenames=true, $morphClassAbbreviations=true)
    {
        $this->namespaceTablenames = $namespaceTablenames;
        $this->morphClassAbbreviations = $morphClassAbbreviations;

        $metadata = [];

        foreach ($classes as $class) {
            $entityMetadata = $this->parseClass($class);

            if ($entityMetadata) {
                $metadata[$class] = $entityMetadata;
            }
        }

        // validate pivot tables
        $this->validator->validatePivotTables($metadata);

        // generate morphable classes
        $this->generateMorphableClasses($metadata);

        return $metadata;
    }

    /**
     * Parse a class.
     *
     * @param string $class
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
     * @param string $class
     * @return array
     */
    public function parseEntity($class)
    {
        $reflectionClass = new ReflectionClass($class);

        // scan class annotations
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        // init class metadata
        $entityMetadata = new EntityDefinition([
            'class' => $class,
            'morphClass' => $this->generateMorphClass($class),
            'table' => new TableDefinition([
                'name' => $this->generateTableName($class),
                'columns' => [],
            ]),
        
            'softDeletes' => false,
            'timestamps' => false,
            'versionable' => false,
            
            'touches' => [],
            'with' => [],

            'columns' => [],
            'attributes' => [],
            'embeddeds' => [],
            'relations' => [],
        ]);

        foreach ($classAnnotations as $annotation) {
            // entity
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Entity) {
                if (! empty($annotation->morphClass)) {
                    $entityMetadata['morphClass'] = $annotation->morphClass;
                }
                if (! empty($annotation->touches)) {
                    $entityMetadata['touches'] = $annotation->touches;
                }
                if (! empty($annotation->with)) {
                    $entityMetadata['with'] = $annotation->with;
                }
            }

            // softdeletes
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\SoftDeletes) {
                $entityMetadata['softDeletes'] = true;
            }

            // table name
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Table) {
                $entityMetadata['table']['name'] = $annotation->value;
            }

            // timestamps
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Timestamps) {
                $entityMetadata['timestamps'] = true;
            }

            // versioned
            if ($annotation instanceof \Wetzel\Datamapper\Annotations\Versionable) {
                $entityMetadata['versionable'] = true;
            }
        }

        // scan property annotations
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $this->getSanitizedName($reflectionProperty->getName(), $entityMetadata['class']);
            $propertyAnnotations = $this->reader->getPropertyAnnotations($reflectionProperty);

            foreach ($propertyAnnotations as $annotation) {
                // property is embedded class
                if ($annotation instanceof \Wetzel\Datamapper\Annotations\Embedded) {
                    $entityMetadata['embeddeds'][] = $this->parseEmbeddedClass($name, $annotation, $entityMetadata);
                }

                // property is column
                if ($annotation instanceof \Wetzel\Datamapper\Annotations\Column) {
                    // try to find @Id annotation -> set primary key
                    foreach ($propertyAnnotations as $subAnnotation) {
                        if ($subAnnotation instanceof \Wetzel\Datamapper\Annotations\Id) {
                            $annotation->primary = true;
                        }
                    }
                    // set column data
                    $columnName = $this->getColumnName($annotation->name ?: $name);

                    $entityMetadata['attributes'][] = $this->parseAttribute($name, $columnName, $annotation);
                    $entityMetadata['table']['columns'][] = $this->parseColumn($columnName, $annotation);
                }

                // property is relationship
                if ($annotation instanceof \Wetzel\Datamapper\Annotations\Relation) {
                    $entityMetadata['relations'][] = $this->parseRelation($name, $annotation, $entityMetadata);
                }
            }
        }

        // check primary key
        $this->validator->validatePrimaryKey($entityMetadata);

        return $entityMetadata;
    }

    /**
     * Parse an embedded class.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $entityMetadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\EmbeddedClass
     */
    protected function parseEmbeddedClass($name, $annotation, &$entityMetadata)
    {
        // check if related class is valid
        $annotation->class = $this->getRealEntity($annotation->class, $entityMetadata['class']);

        $reflectionClass = new ReflectionClass($annotation->class);

        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        // check if class is embedded class
        $this->validator->validateEmbeddedClass($annotation->class, $classAnnotations);

        $embeddedColumnPrefix = ($annotation->columnPrefix || $annotation->columnPrefix === false)
            ? $annotation->columnPrefix
            : $name;

        $embeddedClassMetadata = new EmbeddedClassDefinition([
            'name' => $name,
            'class' => $annotation->class,
            'columnPrefix' => $embeddedColumnPrefix,
            'attributes' => [],
        ]);

        // scan property annotations
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $this->getSanitizedName($reflectionProperty->getName(), $entityMetadata['class']);

            $propertyAnnotations = $this->reader->getPropertyAnnotations($reflectionProperty);

            foreach ($propertyAnnotations as $annotation) {
                // property is column
                if ($annotation instanceof \Wetzel\Datamapper\Annotations\Column) {
                    $columnName = $this->getColumnName($annotation->name ?: $name, $embeddedColumnPrefix);

                    $embeddedClassMetadata['attributes'][] = $this->parseAttribute($name, $columnName, $annotation);
                    $entityMetadata['table']['columns'][] = $this->parseColumn($columnName, $annotation);
                }
            }
        }

        return $embeddedClassMetadata;
    }

    /**
     * Parse an attribute.
     *
     * @param string $name
     * @param string $columnName
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @return \Wetzel\Datamapper\Metadata\Definitions\Attribute
     */
    protected function parseAttribute($name, $columnName, $annotation)
    {
        // add attribute
        return new AttributeDefinition([
            'name' => $name,
            'columnName' => $columnName,
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
        if ($annotation->type == 'string' || $annotation->type == 'char') {
            $options['length'] = $annotation->length;
        }

        // unsigned and autoIncrement option
        if ($annotation->type == 'smallInteger' || $annotation->type == 'integer' || $annotation->type == 'bigInteger') {
            $options['unsigned'] = $annotation->unsigned;
            $options['autoIncrement'] = $annotation->autoIncrement;
        }

        // scale and precision option
        if ($annotation->type == 'decimal') {
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
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $entityMetadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\Relation
     */
    protected function parseRelation($name, $annotation, &$entityMetadata)
    {
        // check if relation type is valid
        $this->validator->validateRelationType($annotation->type);

        // check if we need to add base namespace from configuration
        $annotation->relatedEntity = $annotation->relatedEntity
            ? $this->getRealEntity($annotation->relatedEntity, $entityMetadata['class'])
            : null;
        $annotation->throughEntity = $annotation->throughEntity
            ? $this->getRealEntity($annotation->throughEntity, $entityMetadata['class'])
            : null;

        // change morphedByMany to inverse morphToMany
        if ($annotation->type == 'morphedByMany') {
            $annotation->type = 'morphToMany';
            $annotation->inverse = true;
        }

        // create extra columns for belongsTo
        if ($annotation->type == 'belongsTo') {
            $this->generateBelongsToColumns($name, $annotation, $entityMetadata);
        }
        
        // create extra columns for morphTo
        if ($annotation->type == 'morphTo') {
            $this->generateMorphToColumns($name, $annotation, $entityMetadata);
        }

        $pivotTable = null;

        // create pivot table for belongsToMany
        if ($annotation->type == 'belongsToMany') {
            $pivotTable = $this->generateBelongsToManyPivotTable($name, $annotation, $entityMetadata);
        }

        // create pivot table for morphToMany
        if ($annotation->type == 'morphToMany') {
            $pivotTable = $this->generateMorphToManyPivotTable($name, $annotation, $entityMetadata);
        }

        // add relation
        return new RelationDefinition([
            'name' => $name,
            'type' => $annotation->type,
            'relatedEntity' => $annotation->relatedEntity,
            'pivotTable' => $pivotTable,
            'options' => $this->generateRelationOptionsArray($name, $annotation, $entityMetadata)
        ]);
    }

    /**
     * Generate an options array for a relation.
     *
     * @param string $name
     * @param \Wetzel\Datamapper\Annotations\Annotation $annotation
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $entityMetadata
     * @return array
     */
    protected function generateRelationOptionsArray($name, $annotation, &$entityMetadata)
    {
        $options = [];

        // belongsTo relation
        if ($annotation->type == 'belongsTo') {
            $options['relatedForeignKey'] = $annotation->relatedForeignKey ?: $this->generateKey($annotation->relatedEntity);
            $options['localKey'] = $annotation->localKey ?: 'id';
            $options['relation'] = $annotation->relation;
        }

        // belongsToMany relation
        if ($annotation->type == 'belongsToMany') {
            $options['pivotTable'] = $annotation->pivotTable ?: $this->generatePivotTablename($entityMetadata['class'], $annotation->relatedEntity, $annotation->inverse);
            $options['localPivotKey'] = $annotation->localPivotKey ?: $this->generateKey($entityMetadata['class']);
            $options['relatedPivotKey'] = $annotation->relatedPivotKey ?: $this->generateKey($annotation->relatedEntity);
            $options['relation'] = $annotation->relation;
        }

        // hasMany relation
        if ($annotation->type == 'hasMany') {
            $options['localForeignKey'] = $annotation->localForeignKey ?: $this->generateKey($entityMetadata['class']);
            $options['localKey'] = $annotation->localKey ?: 'id';
        }

        // hasManyThrough relation
        if ($annotation->type == 'hasManyThrough') {
            $options['throughEntity'] = $annotation->throughEntity;
            $options['localForeignKey'] = $annotation->localForeignKey ?: $this->generateKey($entityMetadata['class']);
            $options['throughForeignKey'] = $annotation->throughForeignKey ?: $this->generateKey($annotation->throughEntity);
        }

        // hasOne relation
        if ($annotation->type == 'hasOne') {
            $options['localForeignKey'] = $annotation->localForeignKey ?: $this->generateKey($entityMetadata['class']);
            $options['localKey'] = $annotation->localKey ?: 'id';
        }

        // morphMany relation
        if ($annotation->type == 'morphMany') {
            $options['morphName'] = $annotation->morphName ?: $name;
            $options['morphType'] = $annotation->morphType;
            $options['morphId'] = $annotation->morphId;
            $options['localKey'] = $annotation->localKey ?: 'id';
        }

        // morphOne relation
        if ($annotation->type == 'morphOne') {
            $options['morphName'] = $annotation->morphName ?: $name;
            $options['morphType'] = $annotation->morphType;
            $options['morphId'] = $annotation->morphId;
            $options['localKey'] = $annotation->localKey ?: 'id';
        }

        // morphTo relation
        if ($annotation->type == 'morphTo') {
            $options['morphName'] = $annotation->morphName ?: $name;
            $options['morphType'] = $annotation->morphType;
            $options['morphId'] = $annotation->morphId;
        }

        // morphToMany relation
        if ($annotation->type == 'morphToMany') {
            $options['morphName'] = $annotation->morphName ?: $name;
            $options['pivotTable'] = $annotation->pivotTable ?: $this->generatePivotTablename($entityMetadata['class'], $annotation->relatedEntity, $annotation->inverse, $annotation->morphName);
            if ($annotation->inverse) {
                $options['localPivotKey'] = $annotation->localPivotKey ?: $this->generateKey($entityMetadata['class']);
                $options['relatedPivotKey'] = $annotation->morphName.'_id';
            } else {
                $options['localPivotKey'] = $annotation->morphName.'_id';
                $options['relatedPivotKey'] = $annotation->relatedPivotKey ?: $this->generateKey($annotation->relatedEntity);
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
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $entityMetadata
     * @return void
     */
    protected function generateBelongsToColumns($name, $annotation, &$entityMetadata)
    {
        $relatedForeignKey = $annotation->relatedForeignKey ?: $this->generateKey($annotation->relatedEntity);

        $entityMetadata['table']['columns'][] = new ColumnDefinition([
            'name' => $relatedForeignKey,
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
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $entityMetadata
     * @return void
     */
    protected function generateMorphToColumns($name, $annotation, &$entityMetadata)
    {
        $morphName = (! empty($annotation->morphName))
            ? $annotation->morphName
            : $name;

        $morphId = (! empty($annotation->morphId))
            ? $annotation->morphId
            : $morphName.'_id';

        $morphType = (! empty($annotation->morphType))
            ? $annotation->morphType
            : $morphName.'_type';

        $entityMetadata['table']['columns'][] = new ColumnDefinition([
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

        $entityMetadata['table']['columns'][] = new ColumnDefinition([
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
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $entityMetadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\Table
     */
    protected function generateBelongsToManyPivotTable($name, $annotation, &$entityMetadata)
    {
        $tableName = ($annotation->pivotTable)
            ? $annotation->pivotTable
            : $this->generatePivotTablename($entityMetadata['class'], $annotation->relatedEntity, $annotation->inverse);

        $localPivotKey = $annotation->localForeignKey ?: $this->generateKey($entityMetadata['class']);

        $relatedPivotKey = $annotation->relatedForeignKey ?: $this->generateKey($annotation->relatedEntity);

        return new TableDefinition([
            'name' => $tableName,
            'columns' => [
                new ColumnDefinition([
                    'name' => $localPivotKey,
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => true,
                    'unique' => false,
                    'index' => false,
                    'options' => [
                        'unsigned' => true
                    ]
                ]),
                new ColumnDefinition([
                    'name' => $relatedPivotKey,
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => true,
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
     * @param \Wetzel\Datamapper\Metadata\Definitions\Class $entityMetadata
     * @return \Wetzel\Datamapper\Metadata\Definitions\Table
     */
    protected function generateMorphToManyPivotTable($name, $annotation, &$entityMetadata)
    {
        $morphName = $annotation->morphName;

        $tableName = ($annotation->pivotTable)
            ? $annotation->pivotTable
            : $this->generatePivotTablename($entityMetadata['class'], $annotation->relatedEntity, $annotation->inverse, $morphName);

        if ($annotation->inverse) {
            $pivotKey = $annotation->localPivotKey ?: $this->generateKey($entityMetadata['class']);
        } else {
            $pivotKey = $annotation->relatedPivotKey ?: $this->generateKey($annotation->relatedEntity);
        }

        $morphId = (! empty($annotation->localKey))
            ? $annotation->localKey
            : $morphName.'_id';

        $morphType = $morphName.'_type';

        return new TableDefinition([
            'name' => $tableName,
            'columns' => [
                new ColumnDefinition([
                    'name' => $pivotKey,
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => true,
                    'unique' => false,
                    'index' => false,
                    'options' => [
                        'unsigned' => true
                    ]
                ]),
                new ColumnDefinition([
                    'name' => $morphId,
                    'type' => 'integer',
                    'nullable' => false,
                    'default' => false,
                    'primary' => true,
                    'unique' => false,
                    'index' => false,
                    'options' => [
                        'unsigned' => true
                    ]
                ]),
                new ColumnDefinition([
                    'name' => $morphType,
                    'type' => 'string',
                    'nullable' => false,
                    'default' => false,
                    'primary' => true,
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
    protected function generateMorphableClasses(&$metadata)
    {
        foreach ($metadata as $key => $entityMetadata) {
            foreach ($entityMetadata['relations'] as $relationKey => $relationMetadata) {
                // get morphable classes for morphTo relations
                if ($relationMetadata['type'] == 'morphTo') {
                    $metadata[$key]['relations'][$relationKey]['options']['morphableClasses']
                        = $this->getMorphableClasses($entityMetadata['class'], $relationMetadata['options']['morphName'], $metadata);
                }

                // get morphable classes for morphToMany relations
                if ($relationMetadata['type'] == 'morphToMany' && ! $relationMetadata['options']['inverse']) {
                    $metadata[$key]['relations'][$relationKey]['options']['morphableClasses']
                        = $this->getMorphableClasses($entityMetadata['class'], $relationMetadata['options']['morphName'], $metadata, true);
                }
            }
        }
    }

    /**
     * Get array of morphable classes for a morphTo or morphToMany relation.
     *
     * @param string $relatedEntity
     * @param string $morphName
     * @param array $metadata
     * @param boolean $many
     * @return void
     */
    protected function getMorphableClasses($relatedEntity, $morphName, $metadata, $many=false)
    {
        $morphableClasses = [];

        foreach ($metadata as $entityMetadata) {
            foreach ($entityMetadata['relations'] as $relationMetadata) {
                // check relation type
                if (! ((! $many && $relationMetadata['type'] == 'morphOne')
                    || (! $many && $relationMetadata['type'] == 'morphMany')
                    || ($many && $relationMetadata['type'] == 'morphToMany' && $relationMetadata['options']['inverse']))) {
                    continue;
                }

                // check foreign entity and morph name
                if ($relationMetadata['relatedEntity'] == $relatedEntity
                    && $relationMetadata['options']['morphName'] == $morphName) {
                    $morphableClasses[$entityMetadata['morphClass']] = $entityMetadata['class'];
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
        if ($this->namespaceTablenames) {
            $base = ($inverse)
                ? $this->generateTableName($class1, true)
                : $this->generateTableName($class2, true);

            $related = (! empty($morph))
                ? $morph
                : (! empty($inverse)
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
        if ($this->namespaceTablenames) {
            $className = array_slice(explode('/', str_replace('\\', '/', $class)), 2);

            // delete last entry if entry is equal to the next to last entry
            if (count($className) >= 2 && end($className) == prev($className)) {
                array_pop($className);
            }

            $classBasename = array_pop($className);

            return strtolower(implode('_', array_merge($className, preg_split('/(?<=\\w)(?=[A-Z])/', $classBasename))));
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
        if ($this->morphClassAbbreviations) {
            return snake_case(class_basename($class));
        }

        // eloquent default
        return $class;
    }

    /**
     * Get snake case version of a name.
     *
     * @param string $class
     * @param string $definedClass
     * @return string
     */
    protected function getRealEntity($class, $definedClass)
    {
        $this->validator->validateClass($class, $definedClass);

        return get_real_entity($class);
    }

    /**
     * Get sanitized version of a name.
     *
     * @param string $name
     * @param string $definedClass
     * @return string
     */
    protected function getSanitizedName($name, $definedClass)
    {
        $this->validator->validateName($name, $definedClass);
        
        return $name;
    }

    /**
     * Get column name of a name.
     *
     * @param string $name
     * @param boolean $prefix
     * @return string
     */
    protected function getColumnName($name, $prefix = false)
    {
        $name = snake_case($name);

        if ($prefix) {
            $name = $prefix.'_'.$name;
        }

        return $name;
    }
}
