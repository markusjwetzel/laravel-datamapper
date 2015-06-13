<?php namespace Wetzel\Datamapper\Support;

use ArrayAccess;
use Wetzel\Datamapper\Support\Model;

class Presenter implements ArrayAccess {

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
     * Get an attribute array of all arrayable values.
     *
     * @param  array  $values
     * @return array
     */
    public function getPresentableItems(array $values)
    {
        if (count($this->visible) > 0) {
            return array_intersect_key($values, array_flip($this->visible));
        }

        return array_diff_key($values, array_flip($this->hidden));
    }

    /**
     * Check an attribute against whitelist/blacklist values.
     *
     * @param  string  $value
     * @return array
     */
    public function checkPresentable($value)
    {
        if (count($this->visible) > 0) {
            return count(array_intersect([$value], $this->visible));
        }

        return count(array_diff([$value], $this->hidden));
    }
    
    /**
     * Get the model instance.
     *
     * @param \Wetzel\Datamapper\Support\Model $model
     * @return void
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
    }
    
    /**
     * Get the model instance.
     *
     * @return \Wetzel\Datamapper\Support\Model
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
     * @param  mixed  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key))
        {
            $this->items[] = $value;
        }
        else
        {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
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
        if ($this->checkPresentable($method))
        {
            // method exists in model
            if (is_callable([$this->model, $method])) {
                $item = $this->model->{$method}();

                // item is presentable
                if ($item instanceof \Wetzel\Datamapper\Contracts\Presentable) {
                    return $item->getPresenter();
                }

                // item is collection
                elseif ($item instanceof \Wetzel\Datamapper\Eloquent\Collection) {
                    return $item;
                }

                // item is value
                else {
                    return $item;
                }
            }

            // method exists in this presenter
            elseif (method_exists($this, $method)) {
                return $this->{$method}();
            }
        }

        $class = get_class($this);
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$method() in $file on line $line", E_USER_ERROR);
    }


}