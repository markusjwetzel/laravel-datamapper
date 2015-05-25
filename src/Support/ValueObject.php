<?php namespace Wetzel\DataMapper\Support;

use Illuminate\Contracts\Support\Arrayable;

abstract class ValueObject implements Arrayable {
    
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