<?php namespace Wetzel\DataMapper\Metadata;

use Wetzel\DataMapper\Metadata\Definitions\Class as ClassDefinition;
use Wetzel\DataMapper\Metadata\Definitions\Attribute as AttributeDefinition;
use Wetzel\DataMapper\Metadata\Definitions\Column as ColumnDefinition;
use Wetzel\DataMapper\Metadata\Definitions\Embedded as EmbeddedDefinition;
use Wetzel\DataMapper\Metadata\Definitions\Relation as RelationDefinition;

class Builder {
    
    /**
     * All attribute types supported by Laravel.
     *
     * @var array
     */
    protected $attributeTypes = [
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
    ];

    /**
     * The annotation reader instance.
     *
     * @var \AnnotationReader
     */
    protected $reader;

    /**
     * The metadata from the annotations.
     *
     * @var array
     */
    protected $metadata;

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \AnnotationReader  $reader
     * @return void
     */
    public function __construct(AnnotationReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Parses a class.
     *
     * @param  string  $class
     * @return void
     */
    public function parseEntity($class) {

        $reflectionClass = new ReflectionClass($class);

        // scan class annotations
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        // check if class is entity
        if ( ! $classAnnotations[0] instanceof \Wetzel\DataMapper\Annotations\Entity) {
            return false;
        }

        // init class metadata
        $this->metadata = new ClassDefinition;

        // scan property annotations
        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            $propertyAnnotations = $reader->getPropertyAnnotations($reflectionProperty);

            foreach($propertyAnnotations as $annotation) {
                // property is embedded class
                elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\Embedded) {
                    $this->parseEmbedded($annotation);
                }

                // property is attribute
                if (strpos(get_class($annotation), 'Wetzel\DataMapper\Annotations\Attribute') !== false) {
                    $attributes = $this->parseAttribute($attributes, $name);
                    $this->parseColumn($name, $annotation);
                }

                // property is relationship
                elseif (strpos(get_class($annotation), 'Wetzel\DataMapper\Annotations\Relation') !== false) {
                    $this->parseRelation($name, $annotation);
                }
            }
        }

        foreach($classAnnotations as $annotation) {
            // table name
            if ($annotation instanceof \Wetzel\DataMapper\Annotations\Table) {
                $this->metadata['table'] = $annotation['value'];
            }

            // timestamps
            elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\Timestamps) {
                $this->metadata['timestamps'] = true;
            }

            // softdeletes
            elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\SoftDeletes) {
                $this->metadata['softdeletes'] = true;
            }

            // versioned
            elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\Revisions) {
                $this->metadata['revisions'] = true;
            }
        }
    }

    /**
     * Parses an embedded class.
     *
     * @param  string  $table
     * @return void
     */
    public function parseEmbeddedClass($name, $annotation) {
        $reflectionClass = new ReflectionClass($annotation['value']);

        // scan class annotations
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        // check if class is entity
        if ( ! $classAnnotations[0] instanceof \Wetzel\DataMapper\Annotations\Embedded) {
            return false;
        }

        // scan property annotations
        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            $propertyAnnotations = $reader->getPropertyAnnotations($reflectionProperty);
            
            $attributes = new AttributesDefinition;

            foreach($propertyAnnotations as $annotation) {
                // property is attribute
                if ($annotation instanceof \Wetzel\DataMapper\Annotations\Attribute) {
                    $attributes = $this->parseAttribute($attributes, $name);
                    $this->parseColumn($name, $annotation);
                }
            }

            $this->metadata['embeddeds'][] = new EmbeddedClassDefinition([
                'name' => $name,
                'embeddedClass' => $annotation['value'],
                'attributes' => $attributes,
            ]);
        }
    }

    /**
     * Parses an attribute.
     *
     * @param  \Wetzel\DataMapper\Metadata\Definitions\Attributes  $attributes
     * @param  string  $table
     * @return \Wetzel\DataMapper\Metadata\Definitions\Attributes
     */
    protected function parseAttribute($attributes, $name)
    {
        // add attribute
        return $attributes[] = $name;
    }

    /**
     * Parses a column.
     *
     * @param  string  $table
     * @return void
     */
    protected function parseColumn($name, $annotation)
    {
        // add column
        $this->metadata['columns'][] = new ColumnDefinition([
            'name' => $annotation['value'],
            'type' => $name,
            'nullable' => $annotation['nullable'],
            'default' => $annotation['default'],
            'unsigned' => $annotation['unsigned'],
            'primary' => $annotation['primary'],
            'unique' => $annotation['unique'],
            'index' => $annotation['index'],
            'options' => array_except($annotation, ['value', 'nullable', 'default', 'unsigned', 'primary', 'unique', 'index'],
        ]);
    }

    /**
     * Parses a relationship.
     *
     * @param  string  $table
     * @return void
     */
    protected function parseRelation($name, $annotation)
    {
        // add relation
        $this->metadata['relations'][] = new RelationDefinition([
            'name' => $name,
            'related' => $annotation['value'],
            'options' => array_except($annotation, ['value'],
        ]);

        // create pivots
        if ($name == )
    }

}