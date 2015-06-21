<?php

namespace ProAI\Datamapper\Support;

use ProAI\Datamapper\Contracts\Proxy as ProxyContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use ArrayAccess;
use Exception;

class Proxy implements ProxyContract, ArrayAccess, Arrayable, Jsonable
{
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
        return null;
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $this->getException($method);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->getException($method);
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->getException($method);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->getException($method);
    }

    /**
     * Proxy exception.
     *
     * @param  string  $method
     * @return void
     */
    public function getException($method)
    {
        throw new Exception('You tried to call method "'.$method.'" from a proxy object.');
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
        $this->getException($method);
    }
}
