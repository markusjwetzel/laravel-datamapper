<?php

namespace ProAI\Datamapper\Metadata\Definitions;

use ArrayObject;
use UnexpectedValueException;

abstract class Definition extends ArrayObject
{
    protected $keys;
    
    /**
     * Constructor merges default array with input array.
     *
     * @param array $array
     * @return void
     */
    public function __construct(array $array=[])
    {
        if ($diff = array_diff(array_keys($this->keys), array_keys($array))) {
            throw new UnexpectedValueException('Missing value(s) '.implode(", ", $diff).' in metadata definition '.get_class($this).'.');
        }

        foreach ($array as $name => $value) {
            $this[$name] = $value;
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
            throw new UnexpectedValueException($key.' is not defined in metadata definition '.get_class($this).'.');
        }
    }
}
