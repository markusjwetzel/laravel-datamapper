<?php namespace Wetzel\Datamapper\Support;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

abstract class ValueObject implements Arrayable {
    
    /**
     * Private final constructor, because you should name your constructor's in domain driven design.
     *
     * @return void
     */
    private final function __construct(){
    }
    
    /**
     * Compare two value objects.
     *
     * @param \Wetzel\Datamapper\Support\ValueObject $object
     * @return boolean
     */
    public function equals(ValueObject $object)
    {
        foreach(get_object_vars($this) as $name => $value) {
            if ($this->{$name} !== $object->{$name}) return false;
        }

        return true;
    }
    
    /**
     * Constructor to get an instance from an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $name
     * @return \Wetzel\Datamapper\Support\ValueObject
     */
    public static function newFromEloquentModel(Model $model, $name)
    {
        $object = new static;

        // get model data
        $dict = [
            'mapping' => $model->getMapping(),
            'attributes' => $model->getAttributes()
        ];

        foreach($dict['mapping']['embeddeds'][$name]['attributes'] as $attribute) {
            $object->{$attribute} = $dict['attributes'][$attribute];
        }

        return $object;
    }

    /**
     * Constructor to convert an instance to an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $name
     * @return void
     */
    public function toEloquentModel(Model &$model, $name)
    {
        // get model data
        $dict = [
            'mapping' => $model->getMapping()
        ];

        foreach($dict['mapping']['embeddeds'][$name]['attributes'] as $attribute) {
            $model->setAttribute($attribute, $this->{$attribute});
        }
    }
    
    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];

        foreach(get_object_vars($this) as $name => $value) {
            $array[$name] = $value;
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