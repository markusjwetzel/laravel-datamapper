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

        $model = get_mapped_model($class);

        return (new $model)->newDatamapperQuery();
    }

    /**
     * Create an entity object.
     *
     * @param object $object
     * @return void
     */
    public function insert($object)
    {
        $model = $this->getEloquentModel($object);

        $model->save();
    }

    /**
     * Update an entity object.
     *
     * @param object $object
     * @return void
     */
    public function update($object)
    {
        $model = $this->getEloquentModel($object, true);

        $model->save();
    }

    /**
     * Delete an entity object.
     *
     * @param object $object
     * @return void
     */
    public function delete($object)
    {
        $model = $this->getEloquentModel($object, true);

        //dd($model);

        $model->delete();
    }

    /**
     * Delete an entity object.
     *
     * @param object $object
     * @return \Wetzel\Datamapper\Eloquent\Model
     */
    protected function getEloquentModel($object, $exists=false)
    {
        if (! is_object($object)) {
            throw new Exception('Object transfered to EntityManager is not an object');
        }

        $model = Model::newFromEntity($object);

        $model->exists = $exists;

        return $model;
    }
}
