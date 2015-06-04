<?php namespace Wetzel\Datamapper\Eloquent;

use Illuminate\Database\Eloquent\Collection as BaseCollection;

class Collection extends BaseCollection {

    /**
     * Convert models to plain old php objects.
     *
     * @return \Wetzel\Datamapper\Eloquent\Collection
     */
    public function toEntity()
    {
        foreach($this->items as $name => $item) {
            $this->items[$name] = $item->toEntity();
        }

        return $this;
    }

    /**
     * Convert models to eloquent models.
     *
     * @param \Wetzel\Datamapper\Support\Entity $object
     * @return \Wetzel\Datamapper\Eloquent\Collection
     */
    public static function newFromEntity($objects)
    {
        $models = new static;

        foreach($objects->items as $name => $item) {
            $class = get_class($item);

            $models->items[$name] = $class::newFromEntity($item);
        }

        return $models;
    }

}
