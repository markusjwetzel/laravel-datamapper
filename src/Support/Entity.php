<?php namespace Wetzel\Datamapper\Support;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

abstract class Entity implements Arrayable, Jsonable {
    
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

}