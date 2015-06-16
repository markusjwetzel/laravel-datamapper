<?php

namespace Wetzel\Datamapper\Support;

use Wetzel\Datamapper\Presenter\Repository;
use Wetzel\Datamapper\Presenter\Decorator;
use Wetzel\Datamapper\Support\Presenter;
use Wetzel\Datamapper\Contracts\Model as ModelContract;
use Wetzel\Datamapper\Contracts\Presentable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\App;

abstract class Model implements ModelContract, Presentable, Arrayable, Jsonable
{
    /**
     * The instance of the presenter.
     *
     * @var array
     */
    private $__presenter;

    /**
     * Private final constructor, because you should name your constructor's in domain driven design.
     *
     * @return void
     */
    final protected function __construct()
    {
    }

    /**
     * Get the presenter instance of this model.
     *
     * @return \Wetzel\Datamapper\Support\Presenter
     */
    public function getPresenter()
    {
        if (! $this->__presenter) {
            $presenters = array_flip(Repository::$presenters);

            $class = static::class;

            if (isset($presenters[$class])) {
                $presenter = App::make($presenters[$class]);
            } else {
                $presenter = new Presenter;
            }

            $presenter->setModel($this);

            $this->__presenter = $presenter;
        }

        return $this->__presenter;
    }
    
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
        $presenter = $this->getPresenter();

        $items = $presenter->getPresentableItems(array_except(get_object_vars($this), '__presenter'));

        $array = [];

        foreach ($items as $name => $item) {
            $array[$name] = Decorator::decorate($item, true);
        }

        return $array;
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
        if ($method != '__presenter' && (isset($this->{$method}) || property_exists($this, $method))) {
            return $this->{$method};
        }

        $class = get_class($this);
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$method() in $file on line $line", E_USER_ERROR);
    }
}
