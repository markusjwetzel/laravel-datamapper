<?php

namespace ProAI\Datamapper\Eloquent;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use ProAI\Datamapper\Support\Collection as DatamapperCollection;
use ProAI\Datamapper\Eloquent\Model;

class Collection extends EloquentCollection
{
    /**
     * Convert models to entity objects.
     *
     * @return \ProAI\Datamapper\Support\Collection
     */
    public function toDatamapperObject()
    {
        $entities = new DatamapperCollection;

        foreach ($this->items as $name => $item) {
            $entities->put($name, $item->toDatamapperObject());
        }

        return $entities;
    }

    /**
     * Convert models to data transfer objects.
     *
     * @param string $root
     * @param array $schema
     * @param array $transformations
     * @param string $path
     * @return \ProAI\Datamapper\Support\Collection
     */
    public function toDataTransferObject(string $root, array $schema, array $transformations, $path='')
    {
        $entities = new DatamapperCollection;

        foreach ($this->items as $name => $item) {
            $entities->put($name, $item->toDataTransferObject($root, $schema, $transformations, $path));
        }

        return $entities;
    }

    /**
     * Convert models to eloquent models.
     *
     * @param \ProAI\Datamapper\Support\Collection $entities
     * @param string $lastObjectId
     * @param \ProAI\Datamapper\Eloquent\Model $lastEloquentModel
     * @return \ProAI\Datamapper\Eloquent\Collection
     */
    public static function newFromDatamapperObject($entities, $lastObjectId, $lastEloquentModel)
    {
        $eloquentModels = new static;

        foreach ($entities as $name => $item) {
            if (spl_object_hash($item) == $lastObjectId) {
                $model = $lastEloquentModel;
            } else {
                $model = Model::newFromDatamapperObject($item, $lastObjectId, $lastEloquentModel);
            }

            $eloquentModels->put($name, $model);
        }

        return $eloquentModels;
    }
}
