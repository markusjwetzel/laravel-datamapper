<?php namespace Wetzel\Datamapper\Metadata\Definitions;

use ArrayObject;
use UnexpectedValueException;

abstract class Definition extends ArrayObject {
    
    /**
     * Constructor merges default array with input array.
     *
     * @param array $array
     * @return void
     */
    public function __construct(array $array) {
        $this = array_merge($keys, $array);
    }
    
    /**
     * Do not allow to set more definitions.
     *
     * @param mixed $key
     * @param mixed $newval
     * @return void
     */
    public function offsetSet($key, $newval)
    {
        if ($def = in_array($key, $this->keys)) {
            parent::offsetSet($key, $newval);
        } else {
            throw new UnexpectedValueException('Index is not defined in metadata definition.');
        }
    }

}