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
     * Constructor to get an instance from an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $name
     * @return \Wetzel\Datamapper\Support\ValueObject
     */
    public static function newFromModel(Model $model, $name)
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

}