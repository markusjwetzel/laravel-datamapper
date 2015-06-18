<?php

namespace Wetzel\Datamapper;

use Wetzel\Datamapper\Eloquent\Model;
use Exception;

class EntityManager
{
    /**
     * The config of the datamapper package.
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param array $config
     * @return void
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Set an entity class.
     *
     * @param string $class
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function entity($class)
    {
        $class = get_real_entity($class);

        $eloquentModel = get_mapped_model($class);

        return (new $eloquentModel)->newDatamapperQuery();
    }

    /**
     * Create an entity object.
     *
     * @param object $entity
     * @return void
     */
    public function insert($entity)
    {
        $eloquentModel = $this->getEloquentModel($entity);

        $this->updateRelations($eloquentModel, 'insert');

        $eloquentModel->save();
    }

    /**
     * Update an entity object.
     *
     * @param object $entity
     * @return void
     */
    public function update($entity)
    {
        $eloquentModel = $this->getEloquentModel($entity, true);

        $this->updateRelations($eloquentModel, 'update');

        $eloquentModel->save();
    }

    /**
     * Delete an entity object.
     *
     * @param object $entity
     * @return void
     */
    public function delete($entity)
    {
        $eloquentModel = $this->getEloquentModel($entity, true);

        $this->updateRelations($eloquentModel, 'delete');

        $eloquentModel->delete();
    }

    /**
     * Update a relation.
     *
     * @param \Wetzel\Datamapper\Eloquent\Model $eloquentModel
     * @param string $mode
     * @return void
     */
    protected function updateRelations($eloquentModel, $mode='insert')
    {
        $mapping = $eloquentModel->getMapping();
        $relations = $eloquentModel->getRelations();

        foreach($mapping['relations'] as $name => $relation) {

            // set foreign key for belongsTo/morphTo relation
            if ($relation['type'] == 'belongsTo' || $relation['type'] == 'morphTo') {
                if ($mode == 'insert' || $mode == 'update') {
                    $eloquentModel->{$name}()->associate($relations[$name]);
                }
            }

            // set foreign keys for belongsToMany/morphToMany relation
            if (($relation['type'] == 'belongsToMany' || $relation['type'] == 'morphToMany') && ! $relation['inverse']) {
                // get related keys
                $keys = [];
                foreach($relations[$name] as $item) {
                    $keys[] = $item->getKey();
                }

                // attach/sync/detach keys
                if ($mode == 'insert') {
                    $eloquentModel->{$name}()->attach($keys);
                }
                if ($mode == 'update') {
                    $eloquentModel->{$name}()->sync($keys);
                }
                if ($mode == 'delete') {
                    $eloquentModel->{$name}()->detach($keys);
                }
            }

        }
    }

    /**
     * Delete an entity object.
     *
     * @param object $entity
     * @return \Wetzel\Datamapper\Eloquent\Model
     */
    protected function getEloquentModel($entity, $exists=false)
    {
        if (empty($entity)) {
            throw new Exception('Object transfered to EntityManager is empty');
        }

        if (! is_object($entity)) {
            throw new Exception('Object transfered to EntityManager is not an object');
        }

        $eloquentModel = Model::newFromEntity($entity);

        dd($eloquentModel);

        $eloquentModel->exists = $exists;

        return $eloquentModel;
    }
}
