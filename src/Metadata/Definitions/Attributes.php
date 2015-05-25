<?php namespace Wetzel\DataMapper\Metadata\Definitions;

use UnexpectedValueException;

class Attributes extends Definition {
    
    /**
     * Valid keys.
     * 
     * @var array
     */
    $keys = [
    ];
    
    /**
     * Do not allow to set more definitions.
     *
     * @param mixed $index
     * @param mixed $newval
     * @return void
     */
    public function offsetSet($index, $newval) {
        if ($index == null) {
            parent::offsetSet($index, $newval);
        } else {
            throw new UnexpectedValueException('Only index null is allowed as an Attributes index.');
        }
    }

}