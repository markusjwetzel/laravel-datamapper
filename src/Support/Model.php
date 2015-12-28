<?php

namespace ProAI\Datamapper\Support;

use ProAI\Datamapper\Contracts\Model as ModelContract;

abstract class Model implements ModelContract
{
    /**
     * Protected constructor, because you should name your constructor's in domain driven design.
     *
     * @return void
     */
    protected function __construct()
    {
        //
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
        // magical getter for objects
        if ((isset($this->{$method}) || property_exists($this, $method))&&
            (is_object($this->{$method}) || is_array($this->{$method})))
        {
            return $this->{$method};
        }

        // magical getter for scalars
        $scalarProperty = (substr($method, 0, 3) == 'get')
            ? lcfirst(substr($method, 3))
            : null;
        
        if ($scalarProperty && (isset($this->{$scalarProperty}) || property_exists($this, $scalarProperty)))
        {
            if (is_scalar($this->{$scalarProperty})) {
                return $this->{$scalarProperty};
            }
            if ($this->{$scalarProperty} instanceof \ProAI\Datamapper\Support\ValueObject) {
                return (string) $this->{$scalarProperty};
            }
        }

        $class = get_class($this);
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$method() in $file on line $line", E_USER_ERROR);
    }
}
