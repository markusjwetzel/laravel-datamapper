<?php namespace Wetzel\Datamapper\Metadata\Definitions;

use ArrayObject;
use UnexpectedValueException;

abstract class Definition extends ArrayObject {

    protected $keys;
    
    /**
     * Constructor merges default array with input array.
     *
     * @param array $array
     * @return void
     */
    public function __construct(array $array=[])
    {
        foreach($this->keys as $name => $value) {
            $this[$name] = (isset($array[$name])) ? $array[$name] : $value;
        }
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
        if ($def = in_array($key, array_keys($this->keys))) {
            parent::offsetSet($key, $newval);
        } else {
            throw new UnexpectedValueException($key.' is not defined in metadata definition.');
        }
    }

}