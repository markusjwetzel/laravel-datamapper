<?php namespace Wetzel\Datamapper\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use ReflectionClass;
use Closure;

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
    public function toObject()
    {
        // directly set private properties if entity extends the datamapper entity class (fast!)
        if (is_subclass_of($this->class, 'Wetzel\Datamapper\Support\Entity')) {
            $class = $this->class;
            
            return $class::newFromModel($this);
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
                    ? $this->relations[$name]->toObject()
                    : null;

                $this->setProperty($reflectionClass, $object, $name, $relationObject);
            }

            return $object;
        }
    }

    /**
     * Convert model to plain old php object.
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
     * Convert model to plain old php object.
     *
     * @return string
     */
    public function newFromObject($object)
    {
        $model = $this->newInstance(array(), true);

        // attributes
        foreach($this->mapping['attributes'] as $attribute) {
            $model->setAttribute($reflectionClass->getProperty($attribute)->getValue($object));
        }

        // embeddeds
        foreach($this->mapping['embeddeds'] as $embedded) {
            foreach($this->embedded['attributes'] as $attribute) {
                $model->setAttribute($reflectionClass->getProperty($attribute)->getValue($object));
            }
        }

        // relations
        foreach($this->mapping['relations'] as $name => $relation) {
            // TODO: $relationObject = $this->
            //$reflectionClass->getProperty($name)->setValue($object, $attribute);
        }

        $model->setConnection($connection ?: $this->connection);

        return $model;
    }

    /**
     * Create an instance of a related object.
     *
     * @param string $class
     * @return object
     */
    protected function getRelationObject($class) {
        $reflectionClass = new ReflectionClass($class);

        $object = $reflectionClass->newInstanceWithoutConstructor();

        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            if (in_array($name, $this->attributes)) {
                $reflectionProperty->setValue($object, $this->attributes[$name]);
            }
        }

        return $object;
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

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (isset($this->table)) return $this->table;

        $className = array_slice(explode('/',str_replace('\\', '/', get_class($this))), 2);

        // delete last entry if entry is equal to the next to last entry
        if (count($className) >= 2 && end($className) == prev($className)) {
            array_pop($className);
        }

        $classBasename = array_pop($className);

        return strtolower(implode('_',array_merge($className, preg_split('/(?<=\\w)(?=[A-Z])/', $classBasename))));
    }

}