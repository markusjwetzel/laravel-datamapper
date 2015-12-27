<?php

namespace ProAI\Datamapper\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use ProAI\Datamapper\Eloquent\Collection;
use ProAI\Datamapper\Support\DataTransferObject;
use ProAI\Datamapper\Support\Proxy;
use ProAI\Datamapper\Support\ProxyCollection;
use ProAI\Datamapper\Contracts\Entity as EntityContract;
use ProAI\Datamapper\Eloquent\Builder;
use ProAI\Datamapper\Eloquent\SchemaQuery;
use ReflectionClass;
use ReflectionObject;

class Model extends EloquentModel
{
    /**
     * Mapped class of this model.
     *
     * @var array
     */
    protected $class;

    /**
     * The mapping data for this model.
     *
     * @var array
     */
    protected $mapping;

    /**
     * The auto generated uuid columns for this model.
     *
     * @var array
     */
    protected $autoUuids = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The versioned columns for this model.
     *
     * @var array
     */
    protected $versioned;

    /**
     * The many to many relationship methods.
     *
     * @var array
     */
    public $manyRelations = ['hasMany', 'morphMany', 'belongsToMany', 'morphToMany', 'morphedByMany'];

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $eloquentModels
     * @return \ProAI\Datamapper\Eloquent\Collection
     */
    public function newCollection(array $eloquentModels = array())
    {
        return new Collection($eloquentModels);
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @param string $returnType
     * @return \ProAI\Datamapper\Eloquent\Builder
     */
    public function newQuery($returnType=Builder::RETURN_TYPE_ELOQUENT)
    {
        $builder = $this->newQueryWithoutScopes($returnType);

        return $this->applyGlobalScopes($builder);
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @param string $returnType
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newQueryWithoutScopes($returnType=Builder::RETURN_TYPE_ELOQUENT)
    {
        $builder = $this->newEloquentBuilder(
            $this->newBaseQueryBuilder(),
            $returnType
        );

        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        return $builder->setModel($this)->with($this->with);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \ProAI\Datamapper\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query, $returnType=Builder::RETURN_TYPE_ELOQUENT)
    {
        return new Builder($query, $returnType);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \ProAI\Datamapper\Eloquent\GraphBuilder|static
     */
    public function newGraphQuery()
    {
        return new GraphBuilder($this->newQuery());
    }

    /**
     * Convert model to entity object.
     *
     * @return object
     */
    public function toDatamapperObject()
    {
        // directly set private properties if entity extends the datamapper entity class (fast!)
        if (is_subclass_of($this->class, '\ProAI\Datamapper\Support\Entity')) {
            $class = $this->class;

            return $class::newFromEloquentObject($this);
        }

        // set private properties via reflection (slow!)
        $reflectionClass = new ReflectionClass($this->class);

        $entity = $reflectionClass->newInstanceWithoutConstructor();

        // attributes
        foreach ($this->mapping['attributes'] as $attribute => $column) {
            $this->setProperty(
                $reflectionClass,
                $entity,
                $attribute,
                $this->attributes[$column]
            );
        }

        // embeddeds
        foreach ($this->mapping['embeddeds'] as $name => $embedded) {
            $embeddedReflectionClass = new ReflectionClass($embedded['class']);

            $embeddedObject = $embeddedReflectionClass->newInstanceWithoutConstructor();
            foreach ($embedded['attributes'] as $attribute => $column) {
                // set property
                $this->setProperty(
                    $embeddedReflectionClass,
                    $embeddedObject,
                    $attribute,
                    $this->attributes[$column]
                );
            }

            $this->setProperty(
                $reflectionClass,
                $entity,
                $name,
                $embeddedObject
            );
        }

        // relations
        foreach ($this->mapping['relations'] as $name => $relation) {
            // set relation object
            if (! empty($this->relations[$name])) {
                $relationObject = $this->relations[$name]->toDatamapperObject();
            } elseif (in_array($relation['type'], $this->manyRelations)) {
                $relationObject = new ProxyCollection;
            } else {
                $relationObject = new Proxy;
            }
            
            // set property
            $this->setProperty(
                $reflectionClass,
                $entity,
                $name,
                $relationObject
            );
        }

        return $entity;
    }

    /**
     * Set a private property of an entity.
     *
     * @param \ReflectionClass $reflectionClass
     * @param object $entity
     * @param string $name
     * @param mixed $value
     * @return void
     */
    protected function setProperty(&$reflectionClass, $entity, $name, $value)
    {
        $property = $reflectionClass->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($entity, $value);
    }

    /**
     * Convert model to data transfer object.
     *
     * @param array $root
     * @param array $schema
     * @param array $transformations
     * @param string $path
     * @return object
     */
    public function toDataTransferObject($root, $schema, $transformations, $path='')
    {
        $dto = new DataTransferObject();

        // get morphed schema
        if($this->morphClass && isset($schema['...'.studly_case($this->morphClass)])) {
            $schema = $schema['...'.studly_case($this->morphClass)];
        }

        foreach ($schema as $key => $value) {
            // entry is attribute
            if (is_numeric($key)) {
                // transformation key
                $transformationKey = ($path)
                    ? $root.'.'.$path.'.'.$value
                    : $root.'.'.$value;

                // set value
                if ($value == '__type') {
                    $dto->{$value} = class_basename($this->class);
                } elseif (isset($transformations[$transformationKey])) {
                    $node = new GraphNode;
                    $transformations[$transformationKey]($node, $this->attributes);
                    $dto->{$value} = $node->getValue();
                } elseif (isset($transformations['*.'.$value])) {
                    $node = new GraphNode;
                    $transformations['*.'.$value]($node, $this->attributes);
                    $dto->{$value} = $node->getValue();
                } elseif (isset($this->attributes[$value])) {
                    $dto->{$value} = $this->attributes[$value];
                }
            }

            // entry is relation
            if (! is_numeric($key) && isset($this->relations[$key])) {
                // set value and transform childs to dtos
                $newPath = ($path) ? $path.'.'.$key : $key;
                $dto->{$key} = $this->relations[$key]->toDataTransferObject(
                    $root,
                    $value,
                    $transformations,
                    $newPath
                );
            }
        }

        return $dto;
    }

    /**
     * Convert model to plain old php object.
     *
     * @param \ProAI\Datamapper\Contracts\Entity $entity
     * @param string $lastObjectId
     * @param \ProAI\Datamapper\Eloquent\Model $lastEloquentModel
     * @return \ProAI\Datamapper\Eloquent\Model
     */
    public static function newFromDatamapperObject(EntityContract $entity, $lastObjectId = null, $lastEloquentModel = null)
    {
        // directly get private properties if entity extends the datamapper entity class (fast!)
        if ($entity instanceof \ProAI\Datamapper\Support\Entity) {
            return $entity->toEloquentObject($lastObjectId, $lastEloquentModel);
        }

        // get private properties via reflection (slow!)
        $class = get_mapped_model(get_class($entity));

        $eloquentModel = new $class;

        $reflectionObject = new ReflectionObject($entity);

        $mapping = $eloquentModel->getMapping();

        // attributes
        foreach ($mapping['attributes'] as $attribute => $column) {
            if (! $eloquentModel->isAutomaticallyUpdatedDate($column)) {
                // get property
                $property = $eloquentModel->getProperty(
                    $reflectionObject,
                    $entity,
                    $attribute
                );

                // set attribute
                $eloquentModel->setAttribute($column, $property);
            }
        }

        // embeddeds
        foreach ($mapping['embeddeds'] as $name => $embedded) {
            $embeddedObject = $eloquentModel->getProperty($reflectionObject, $entity, $name);


            if (! empty($embeddedObject)) {
                $embeddedReflectionObject = new ReflectionObject($embeddedObject);

                foreach ($embedded['attributes'] as $attribute => $column) {
                    // get property
                    $property = $eloquentModel->getProperty(
                        $embeddedReflectionObject,
                        $embeddedObject,
                        $attribute
                    );

                    // set attribute
                    $eloquentModel->setAttribute($column, $property);
                }
            }
        }

        // relations
        foreach ($mapping['relations'] as $name => $relation) {
            $relationObject = $eloquentModel->getProperty(
                $reflectionObject,
                $entity,
                $name
            );

            if (! empty($relationObject) && ! $relationObject instanceof \ProAI\Datamapper\Contracts\Proxy) {
                // set relation
                if ($relationObject instanceof \ProAI\Datamapper\Support\Collection) {
                    $value = EloquentCollection::newFromDatamapperObject($relationObject, $this, $eloquentModel);
                } elseif (spl_object_hash($relationObject) == $lastObjectId) {
                    $value = $lastEloquentModel;
                } else {
                    $value = EloquentModel::newFromDatamapperObject($relationObject, spl_object_hash($this), $eloquentModel);
                }
                
                $eloquentModel->setRelation($name, $value);
            }
        }

        return $eloquentModel;
    }

    /**
     * Check if attribute is auto generated and updated date.
     *
     * @param string $attribute
     * @return boolean
     */
    public function isAutomaticallyUpdatedDate($attribute)
    {
        // soft deletes
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(static::class)) && $attribute == $this->getDeletedAtColumn()) {
            return true;
        }

        // timestamps
        if ($this->timestamps && ($attribute == $this->getCreatedAtColumn() || $attribute == $this->getUpdatedAtColumn())) {
            return true;
        }
        
        return false;
    }

    /**
     * Get a private property of an entity.
     *
     * @param \ReflectionObject $reflectionObject
     * @param object $entity
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    protected function getProperty($reflectionObject, $entity, $name)
    {
        $property = $reflectionObject->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($entity);
    }

    /**
     * Update auto inserted/updated fields.
     *
     * @param \ProAI\Datamapper\Contracts\Entity $entity
     * @param string $action
     * @return void
     */
    public function afterSaving($entity, $action)
    {
        // set private properties via reflection (slow!)
        $reflectionClass = new ReflectionClass($this->class);

        if ($updateFields = $this->getAutomaticallyUpdatedFields($entity, $action)) {

            // attributes
            foreach ($this->mapping['attributes'] as $attribute => $column) {
                if (in_array($column, $updateFields)) {
                    $this->setProperty(
                        $reflectionClass,
                        $entity,
                        $attribute,
                        $this->attributes[$column]
                    );
                }
            }

            // embeddeds
            foreach ($this->mapping['embeddeds'] as $name => $embedded) {
                $embeddedReflectionClass = new ReflectionClass($embedded['class']);

                $embeddedObject = $this->getProperty(
                    $reflectionClass,
                    $entity,
                    $name
                );
                
                if (empty($embeddedObject)) {
                    $embeddedObject = $embeddedReflectionClass->newInstanceWithoutConstructor();
                }

                foreach ($embedded['attributes'] as $attribute => $column) {
                    if (in_array($column, $updateFields)) {
                        $this->setProperty(
                            $embeddedReflectionClass,
                            $embeddedObject,
                            $attribute,
                            $this->attributes[$column]
                        );
                    }
                }

                $this->setProperty(
                    $reflectionClass,
                    $entity,
                    $name,
                    $embeddedObject
                );
            }

        }
    }

    /**
     * Get auto inserted/updated fields.
     *
     * @param object $entity
     * @param string $action
     * @return void
     */
    protected function getAutomaticallyUpdatedFields($entity, $action)
    {
        $updateFields = [];

        // auto increment
        if ($action == 'insert' && $this->incrementing) {
            $updateFields[] = $this->getKeyName();
        }

        // auto uuid
        if ($action == 'insert' && method_exists($this, 'bootAutoUuid')) {
            $updateFields = array_merge($this->autoUuids, $updateFields);
        }

        // timestamps
        if ($this->timestamps) {
            if ($action == 'insert') {
                $updateFields[] = $this->getCreatedAtColumn();
            }
            $updateFields[] = $this->getUpdatedAtColumn();
        }
        
        // soft deletes
        if ($action == 'update' && method_exists($this, 'bootSoftDeletes')) {
            $updateFields[] = $this->getDeletedAtColumn();
        }

        return $updateFields;
    }

    /**
     * Get the mapping data.
     *
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }
}
