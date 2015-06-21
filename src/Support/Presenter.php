<?php

namespace ProAI\Datamapper\Support;

use Exception;
use BadMethodCallException;
use ArrayAccess;
use ProAI\Datamapper\Support\Model;
use ProAI\Datamapper\Presenter\Decorator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class Presenter implements ArrayAccess, Arrayable, Jsonable
{
    /**
     * The instance of the presentable model.
     *
     * @var array
     */
    protected $model;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = array();

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $visible = array();

    /**
     * The attributes that are added by the presenter.
     *
     * @var array
     */
    protected $added;

    /**
     * Get an attribute array of all arrayable values.
     *
     * @param  array  $values
     * @param boolean $refresh
     * @return array
     */
    public function getPresentableItems(array $values, $refresh = false)
    {
        // parse items only if instance is not base presenter
        if (static::class != self::class) {
            $addedValues = [];

            foreach ($this->getAdded() as $key) {
                $addedValues[$key] = $this->{$key}();
            }

            $values = array_merge($values, $addedValues);

            if (count($this->visible) > 0) {
                return array_intersect_key($values, array_flip($this->visible));
            }

            return array_diff_key($values, array_flip($this->hidden));
        }

        return $values;
    }

    /**
     * Get added attributes.
     *
     * @return array
     */
    public function getAdded()
    {
        // add attributes defined by the presenter
        if (! $this->added) {
            $added = array_diff_key(
                array_flip(get_class_methods(static::class)),
                array_flip(get_class_methods(self::class))
            );

            array_forget($added, '__construct');
            
            $this->added = array_flip($added);
        }

        return $this->added;
    }

    /**
     * Check an attribute against whitelist/blacklist values.
     *
     * @param  string  $key
     * @return array
     */
    protected function checkPresentable($key)
    {
        if (count($this->visible) > 0) {
            return count(array_intersect([$key], $this->visible));
        }

        return count(array_diff([$key], $this->hidden));
    }
    
    /**
     * Get the model instance.
     *
     * @param \ProAI\Datamapper\Support\Model $model
     * @return void
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
    }
    
    /**
     * Get the model instance.
     *
     * @return \ProAI\Datamapper\Support\Model
     */
    public function getModel()
    {
        return $this->model;
    }
    
    /**
     * Convert the entity instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return $this->model->toJson($options);
    }
    
    /**
     * Convert the entity instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->model->toArray();
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        try {
            $this->offsetGet($offset);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if ($offset != snake_case($offset)) {
            throw new Exception('Offset "'.$offset.'" is not snake case');
        }

        $offset = camel_case($offset);

        if (method_exists($this, $offset)) {
            return $this->{$offset}();
        } else {
            return $this->__call($offset, []);
        }
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
        throw new Exception('Presenter cannot set offset "'.$offset.'" to "'.$value.'" in '.get_class());
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        throw new Exception('Presenter cannot unset offset "'.$offset.'" in '.get_class());
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
        if ($this->checkPresentable($method)) {
            $item = $this->model->{$method}();

            return Decorator::decorate($item);
        } else {
            throw new BadMethodCallException('Method '.$method.' is hidden for presentation');
        }
    }
}
