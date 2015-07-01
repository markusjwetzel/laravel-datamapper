<?php

namespace ProAI\Datamapper;

use ProAI\Datamapper\Eloquent\Model;
use Exception;
use ReflectionObject;

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

        $eloquentModel->updateEntityAfterSaving($entity, 'insert');
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

        $eloquentModel->updateEntityAfterSaving($entity, 'update');
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
     * Update relations.
     *
     * @param \ProAI\Datamapper\Eloquent\Model $eloquentModel
     * @param string $action
     * @return void
     */
    protected function updateRelations($eloquentModel, $action='insert')
    {
        $mapping = $eloquentModel->getMapping();
        $eloquentRelations = $eloquentModel->getRelations();

        foreach($mapping['relations'] as $name => $relationMapping) {
            if (isset($eloquentRelations[$name])) {
                $this->updateRelation($eloquentModel, $name, $relationMapping, $action);
            }
        }
    }

    /**
     * Update a relation.
     *
     * @param \ProAI\Datamapper\Eloquent\Model $eloquentModel
     * @param string $name
     * @param array $relationMapping
     * @param string $action
     * @return void
     */
    protected function updateRelation($eloquentModel, $name, $relationMapping, $action='insert')
    {
        // set foreign key for belongsTo/morphTo relation
        if ($relationMapping['type'] == 'belongsTo' || $relationMapping['type'] == 'morphTo') {
            $this->updateBelongsToRelation($eloquentModel, $name, $action);
        }

        // set foreign keys for belongsToMany/morphToMany relation
        if (($relationMapping['type'] == 'belongsToMany' || $relationMapping['type'] == 'morphToMany') && ! $relationMapping['inverse']) {
            $this->updateBelongsToManyRelation($eloquentModel, $name, $action);
        }
    }

    /**
     * Update a belongsTo or morphTo relation.
     *
     * @param \ProAI\Datamapper\Eloquent\Model $eloquentModel
     * @param string $name
     * @param string $action
     * @return void
     */
    protected function updateBelongsToRelation($eloquentModel, $name, $action='insert')
    {
        if ($action == 'insert' || $action == 'update') {
            $eloquentModel->{$name}()->associate($eloquentModel->getRelation($name));
        }
    }

    /**
     * Update a belongsToMany or morphToMany relation.
     *
     * @param \ProAI\Datamapper\Eloquent\Model $eloquentModel
     * @param string $name
     * @param string $action
     * @return void
     */
    protected function updateBelongsToManyRelation($eloquentModel, $name, $action='insert')
    {
        $eloquentCollection = $eloquentModel->getRelation($name);

        if (! $eloquentCollection instanceof \Illuminate\Database\Eloquent\Collection) {
            throw new Exception("Many-to-many relation '".$name."' is not an array collection");
        }

        // get related keys
        $keys = [];

        foreach($eloquentCollection as $item) {
            $keys[] = $item->getKey();
        }

        // attach/sync/detach keys
        if ($action == 'insert') {
            $eloquentModel->{$name}()->attach($keys);
        }
        if ($action == 'update') {
            $eloquentModel->{$name}()->sync($keys);
        }
        if ($action == 'delete') {
            $eloquentModel->{$name}()->detach($keys);
        }
    }

    /**
     * Update auto inserted/updated fields.
     *
     * @param object $entity
     * @param \ProAI\Datamapper\Eloquent\Model $eloquentModel
     * @param string $action
     * @return void
     */
    protected function updateAutoFields($entity, $eloquentModel, $action = 'insert')
    {
        $autoFields = $this->getAutoFields($entity, $eloquentModel, $action);

        // set auto updated fields via reflection class
        if ($autoFields) {
            $reflectionObject = new ReflectionObject($entity);

            foreach($autoFields as $key => $value) {
                $property = $reflectionObject->getProperty($key);
                $property->setAccessible(true);
                $property->setValue($entity, $value);
            }
        }
    }

    /**
     * Get auto inserted/updated fields.
     *
     * @param object $entity
     * @param \ProAI\Datamapper\Eloquent\Model $eloquentModel
     * @param string $action
     * @return void
     */
    protected function getAutoFields($entity, $eloquentModel, $action = 'insert')
    {
        $autoFields = [];

        // auto increment
        if ($action == 'insert' && $eloquentModel->incrementing) {
            $autoFields[camel_case($eloquentModel->getKeyName())] = $eloquentModel->getKey();
        }

        // auto uuid
        if ($action == 'insert' && method_exists($eloquentModel, 'bootAutoUuid')) {
            foreach($eloquentModel->getAutoUuids() as $key => $value) {
                $autoFields[camel_case($eloquentModel->getKeyName())] = $eloquentModel->getKey();
            }
        }

        // timestamps
        if ($eloquentModel->timestamps) {
            if ($action == 'insert') {
                $autoFields['createdAt'] = $eloquentModel->getAttribute('created_at');
            }
            $autoFields['updatedAt'] = $eloquentModel->getAttribute('updated_at');
        }
        
        // soft deletes
        if ($action == 'update' && method_exists($eloquentModel, 'bootSoftDeletes')) {
            $autoFields['deletedAt'] = $eloquentModel->getAttribute('deleted_at');
        }

        return $autoFields;
    }

    /**
     * Delete an entity object.
     *
     * @param object $entity
     * @return \ProAI\Datamapper\Eloquent\Model
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

        $eloquentModel->exists = $exists;

        return $eloquentModel;
    }
}
