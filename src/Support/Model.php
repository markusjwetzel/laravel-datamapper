<?php namespace Wetzel\Datamapper\Support;

use Wetzel\Datamapper\Presenter\Repository;
use Wetzel\Datamapper\Presenter\Storage;
use Wetzel\Datamapper\Support\Presenter;

use Wetzel\Datamapper\Contracts\Presentable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

abstract class Model implements Presentable, Arrayable, Jsonable {
    
    /**
     * Private final constructor, because you should name your constructor's in domain driven design.
     *
     * @return void
     */
    protected final function __construct() {
    }

    /**
     * Get the presenter instance of this model.
     *
     * @return \Wetzel\Datamapper\Support\Presenter
     */
    public function getPresenter()
    {
        $objectHash = spl_object_hash($this);

        if ( ! Storage::has($objectHash)) {
            $presenters = Repository::$presenters;

            $class = static::class;

            if (isset($presenters[$class])) {
                $presenter = new $presenters[$class];
            } else {
                $presenter = new Presenter;
            }

            $presenter->setModel($this);

            Storage::add($objectHash, $presenter);
        }

        return Storage::get($objectHash);
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

        $items = $presenter->getPresentableItems(get_object_vars($this));

        $array = [];

        foreach($items as $name => $item) {
            // item is presentable
            if ($item instanceof \Wetzel\Datamapper\Contracts\Presentable) {
                $array[$name] = $item->getPresenter()->toArray();
            }

            // item is collection
            elseif ($item instanceof \Wetzel\Datamapper\Eloquent\Collection) {
                $array[$name] = $item->toArray();
            }

            // item is value
            else {
                $array[$name] = (string) $presenter->{$name}();
            }
        }

        /*$addedItems = $presenter->getAddedVars($items);

        foreach($addedItems as $name => $item) {

        }

        dd(array_diff_key($items, array_flip(get_class_methods($presenter))));*/
        // todo: extra presenter vars

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