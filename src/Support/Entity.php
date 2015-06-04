<?php namespace Wetzel\Datamapper\Support;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

abstract class Entity implements Arrayable, Jsonable {
    
    /**
     * Private final constructor, because you should name your constructor's in domain driven design.
     *
     * @return void
     */
    private final function __construct(){
    }

    /**
     * Constructor to get an instance from an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Wetzel\Datamapper\Support\Entity
     */
    public static function newFromEloquentModel(Model $model)
    {
        $object = new static;

        // get model data
        $dict = [
            'mapping' => $model->getMapping(),
            'attributes' => $model->getAttributes(),
            'relations' => $model->getRelations()
        ];

        // attributes
        foreach($dict['mapping']['attributes'] as $attribute) {
            $object->{$attribute} = $dict['attributes'][$attribute];
        }

        // embeddeds
        foreach($dict['mapping']['embeddeds'] as $name => $embedded) {
            $object->{$name} = $embedded['class']::newFromEloquentModel($model, $name);
        }

        // relations
        foreach($dict['mapping']['relations'] as $name => $relation) {
            $object->{$name} = ( ! empty($dict['relations'][$name]))
                ? $dict['relations'][$name]->toEntity()
                : null;
        }

        return $object;
    }

    /**
     * Constructor to convert an instance to an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Wetzel\Datamapper\\Eloquent\Model
     */
    public function toEloquentModel(Model $model)
    {
        // get model data
        $dict = [
            'mapping' => $model->getMapping(),
            'attributes' => $model->getAttributes(),
            'relations' => $model->getRelations()
        ];

        // attributes
        foreach($dict['mapping']['attributes'] as $attribute) {
            $model->setAttribute($attribute, $this->{$attribute});
        }

        // embeddeds
        foreach($dict['mapping']['embeddeds'] as $name => $embedded) {
            $embeddedObject = $this->{$name};

            $embeddedObject->toEloquentModel($model, $name);
        }

        // relations
        foreach($dict['mapping']['relations'] as $name => $relation) {
            $relationObject = $this->{$name};

            if ( ! empty($relationObject)) {
                $class = ($relationObject instanceof \Wetzel\Datamapper\Eloquent\Collection)
                    ? '\Wetzel\Datamapper\Eloquent\Collection'
                    : '\Wetzel\Datamapper\Eloquent\Model';

                $model->setRelation($name, $class::newFromEntity($relationObject)); // or newFromEloquentModel??
            }
        }

        return $model;
    }
    
    /**
     * Convert the entity instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * Convert the entity instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];

        foreach(get_object_vars($this) as $name => $value) {
            if (is_object($value)) {
                $array[$name] = $value->toArray();
            } else {
                $array[$name] = $value;
            }
        }

        return $array;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // magical getter
        if (isset($this->{$method}) || property_exists($this, $method)) {
            return $this->{$method};
        }
    }

}