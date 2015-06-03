<?php namespace Wetzel\Datamapper\Eloquent;

use Illuminate\Database\Eloquent\Collection as BaseCollection;

class Collection extends BaseCollection {

    /**
     * Convert models to plain old php objects.
     *
     * @return void
     */
    public function toObject()
    {
        foreach($this->items as $name => $item) {
            $this->items[$name] = $item->toObject();
        }
    }

    /**
     * Convert models to eloquent models.
     *
     * @return void
     */
    public function toEloquent()
    {
        foreach($this->items as $name => $item) {
            $this->items[$name] = $item->toObject();
        }
    }

}
