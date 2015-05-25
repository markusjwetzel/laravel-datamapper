<?php namespace Wetzel\DataMapper\Metadata;

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
     * Get all tablenames of a database.
     *
     * @param  string  $table
     * @return void
     */
    public function parseEntity($class) {
        $reader = new SimpleAnnotationReader();
        $reflectionClass = new ReflectionClass('Examunity\LaravelDataMapper\AnnotationDemo');

        // scan class annotations
        $classAnnotations = $this->reader->getClassAnnotations($reflectionClass);

        // check if class is entity
        if ( ! $classAnnotations[0] instanceof \Wetzel\DataMapper\Annotations\Entity) {
            return false;
        }

        // scan property annotations
        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();
            $propertyAnnotations = $reader->getPropertyAnnotations($reflectionProperty);

            foreach($propertyAnnotations as $annotation) {

                // embedded
                elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\Embedded) {
                    $this->parseEmbedded($annotation);
                }

                // column
                if ($annotation instanceof \Wetzel\DataMapper\Annotations\Attribute) {
                    $this->parseAttribute($name, $annotation);
                }

                // relationship
                elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\Relation) {
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
     * Get all tablenames of a database.
     *
     * @param  string  $table
     * @return void
     */
    public function parseEmbedded($name, $annotation) {
        $reflectionClass = new ReflectionClass('Examunity\LaravelDataMapper\AnnotationDemo');

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

            foreach($propertyAnnotations as $annotation) {

                // column
                if ($annotation instanceof \Wetzel\DataMapper\Annotations\Attribute) {
                    $this->parseAttribute($name, $annotation);
                }

            }

        }
    }

    /**
     * Get all tablenames of a database.
     *
     * @param  string  $table
     * @return void
     */
    protected function parseAttribute($name, $annotation) {
        if (in_array($annotation['value'], $this->attributeTypes) {

            $options = [];

            // scale & decimal option for decimal and double
            if (in_array($annotation['value'], ['decimal','double'])) {
                $options = [
                    $annotation['precision'],
                    $annotation['scale'],
                ];
            }

            // values option for enum
            elseif (in_array($annotation['value'], ['enum'])) {
                $options = [
                    $annotation['values'],
                ];
            }

            // length option for char and string
            elseif (in_array($annotation['value'], ['char','string']) && isset($annotation['length'])) {
                $options = [
                    $annotation['length'],
                ];
            }
            
            $this->metadata['attributes'][$name] = [
                'type' => $annotation['value'],
                'options' => $options,
                'nullable' => $annotation['nullable'],
                'default' => $annotation['default'],
                'unsigned' => $annotation['unsigned'],
                'primary' => $annotation['primary'],
                'unique' => $annotation['unique'],
                'index' => $annotation['index'],
        }
    }

    /**
     * Get all tablenames of a database.
     *
     * @param  string  $table
     * @return void
     */
    protected function parseRelation($name, $annotation) {
        if ($annotation instanceof \Wetzel\DataMapper\Annotations\BelongsTo) {
            $relation = [
                $annotation['related'],
                $annotation['foreignKey'],
                $annotation['otherKey'],
                $annotation['relation'],
            ];
        }
        elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\BelongsToMany) {
            $relation = [
                $annotation['related'],
                $annotation['table'],
                $annotation['foreignKey'],
                $annotation['otherKey'],
                $annotation['relation'],
            ];
        }
        elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\HasMany) {
            $relation = [
                $annotation['related'],
                $annotation['foreignKey'],
                $annotation['localKey'],
            ];
        }
        elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\HasManyThrough) {
            $relation = [
                $annotation['related'],
                $annotation['through'],
                $annotation['firstKey'],
                $annotation['secondKey'],
            ];
        }
        elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\HasOne) {
            $relation = [
                $annotation['related'],
                $annotation['foreignKey'],
                $annotation['localKey'],
            ];

        }
        elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\MorphMany) {
            $relation = [
                $annotation['related'],
                $annotation['name'],
                $annotation['type'],
                $annotation['id'],
                $annotation['localKey'],
            ];
        }
        elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\MorphOne) {
            $relation = [
                $annotation['related'],
                $annotation['name'],
                $annotation['type'],
                $annotation['id'],
                $annotation['localKey'],
            ];
        }
        elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\MorphTo) {
            $relation = [
                $annotation['related'],
                $annotation['type'],
                $annotation['id'],
            ];
        }
        elseif ($annotation instanceof \Wetzel\DataMapper\Annotations\MorphToMany) {
            $relation = [
                $annotation['related'],
                $annotation['table'],
                $annotation['foreignKey'],
                $annotation['otherKey'],
                $annotation['reverse'],
            ];
        }

        $this->metadata['relations'][$name] = $relation;
    }

}