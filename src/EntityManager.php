<?php namespace Wetzel\Datamapper;

use Wetzel\Datamapper\Eloquent\Model;
use Exception;

class EntityManager {

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
     * @return Eloquent
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