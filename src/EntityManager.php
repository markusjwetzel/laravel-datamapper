<?php namespace Wetzel\Datamapper;

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

        return (new $model)->newDatamapperQuery();
    }

    /**
     * Create an entity object.
     * 
     * @param Entity $object
     * @return void
     */
    public function create($object)
    {
        $model = '\Wetzel\Datamapper\Cache\Entity' . md5($class);

        dd($model::newFromEntity($object));
    }

    /**
     * Update an entity object.
     * 
     * @param Entity $object
     * @return void
     */
    public function update($object)
    {
        $class = get_class($object);
        dd($class);
        $model = '\Wetzel\Datamapper\Cache\Entity' . md5($class);

        dd($model::newFromEntity($object));
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