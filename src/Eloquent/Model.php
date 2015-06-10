<?php namespace Wetzel\Datamapper\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use ReflectionClass;
use ReflectionObject;

class Model extends EloquentModel {

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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = array('*');

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = array();

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Wetzel\Datamapper\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return \Wetzel\Datamapper\Eloquent\Builder
     */
    public function newDatamapperQuery()
    {
        $builder = $this->newDatamapperQueryWithoutScopes();

        return $this->applyGlobalScopes($builder);
    }

    /**
     * Get a new query builder that doesn't have any global scopes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newDatamapperQueryWithoutScopes()
    {
        $builder = $this->newDatamapperEloquentBuilder(
            $this->newBaseQueryBuilder()
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
     * @return \Wetzel\Datamapper\Eloquent\Builder|static
     */
    public function newDatamapperEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Convert model to plain old php object.
     *
     * @return object
     */
    public function toEntity()
    {
        // directly set private properties if entity extends the datamapper entity class (fast!)
        if (is_subclass_of($this->class, '\Wetzel\Datamapper\Support\Entity')) {
            $class = $this->class;

            return $class::newFromEloquentModel($this);
        }

        // set private properties via reflection (slow!)
        else {
            $reflectionClass = new ReflectionClass($this->class);

            $object = $reflectionClass->newInstanceWithoutConstructor();

            // attributes
            foreach($this->mapping['attributes'] as $attribute) {
                $this->setProperty($reflectionClass, $object, $attribute, $this->attributes[$attribute]);
            }

            // embeddeds
            foreach($this->mapping['embeddeds'] as $name => $embedded) {
                $embeddedReflectionClass = new ReflectionClass($embedded['class']);

                $embeddedObject =  $embeddedReflectionClass->newInstanceWithoutConstructor();
                foreach($embedded['attributes'] as $attribute) {
                    $this->setProperty($embeddedReflectionClass, $embeddedObject, $attribute, $this->attributes[$attribute]);
                }

                $this->setProperty($reflectionClass, $object, $name, $embeddedObject);
            }

            // relations
            foreach($this->mapping['relations'] as $name => $relation) {
                $relationObject = ( ! empty($this->relations[$name]))
                    ? $this->relations[$name]->toEntity()
                    : null;

                $this->setProperty($reflectionClass, $object, $name, $relationObject);
            }

            return $object;
        }
    }

    /**
     * Convert model to plain old php object.
     *
     * @return string
     */
    public static function newFromEntity($object)
    {
        $class = '\Wetzel\Datamapper\Cache\Entity' . md5(get_class($object));

        $model = new $class;

        // directly get private properties if entity extends the datamapper entity class (fast!)
        if ( ! is_subclass_of($object, '\Wetzel\Datamapper\Support\Entity')) {
            return $object->toEloquentModel($model);
        }

        // get private properties via reflection (slow!)
        else {
            $reflectionObject = new ReflectionObject($object);

            $mapping = $model->getMapping();

            // attributes
            foreach($mapping['attributes'] as $attribute) {
                $model->setAttribute($attribute, $model->getProperty($reflectionObject, $object, $attribute));
            }

            // embeddeds
            foreach($mapping['embeddeds'] as $name => $embedded) {
                $embeddedObject = $model->getProperty($reflectionObject, $object, $name);

                $embeddedReflectionObject = new ReflectionObject($embeddedObject);

                foreach($embedded['attributes'] as $attribute) {
                    $model->setAttribute($attribute, $model->getProperty($embeddedReflectionObject, $embeddedObject, $attribute));
                }
            }

            // relations
            foreach($mapping['relations'] as $name => $relation) {
                $relationObject = $model->getProperty($reflectionObject, $object, $name);

                if ( ! empty($relationObject)) {
                    $class = ($relationObject instanceof \Wetzel\Datamapper\Eloquent\Collection)
                        ? '\Wetzel\Datamapper\Eloquent\Collection'
                        : '\Wetzel\Datamapper\Eloquent\Model';

                    $model->setRelation($name, $class::newFromEntity($relationObject));
                }
            }

            return $model;
        }
    }

    /**
     * Set a private property of an entity.
     *
     * @param \ReflectionClass $reflectionClass
     * @param object $object
     * @param string $name
     * @param mixed $value
     * @return void
     */
    protected function setProperty(&$reflectionClass, $object, $name, $value)
    {
        $property = $reflectionClass->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * Get a private property of an entity.
     *
     * @param \ReflectionObject $reflectionObject
     * @param object $object
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    protected function getProperty($reflectionObject, $object, $name)
    {
        $property = $reflectionObject->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($object);
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