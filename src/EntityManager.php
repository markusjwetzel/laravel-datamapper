<?php namespace Wetzel\Datamapper;

use Wetzel\Datamapper\Eloquent\Model;
use Exception;

class EntityManager {

    /**
     * Set an entity class.
     * 
     * @param string $class
     * @return Eloquent
     */
    public function set($class)
    {
        $model = '\Wetzel\Datamapper\Cache\Entity' . md5($class);

        if (class_exists($model)) {
            return (new $model)->newDatamapperQuery();
        } else {
            throw new Exception('There is no cached Eloquent class for class "'.$class.'".');
        }
    }

    /**
     * Create an entity object.
     * 
     * @param Entity $object
     * @return void
     */
    public function create($object)
    {
        dd(Model::newFromEntity($object));
    }

    /**
     * Update an entity object.
     * 
     * @param Entity $object
     * @return void
     */
    public function update($object)
    {
        dd(Model::newFromEntity($object));
    }

    /**
     * Delete an entity object.
     * 
     * @param Entity $object
     * @return void
     */
    public function delete($object)
    {

    }

}