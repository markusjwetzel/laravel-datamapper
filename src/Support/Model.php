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

        $class = get_class($this);
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$method() in $file on line $line", E_USER_ERROR);
    }
}
