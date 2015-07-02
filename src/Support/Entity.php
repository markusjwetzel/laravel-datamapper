<?php

namespace ProAI\Datamapper\Support;

use ProAI\Datamapper\Eloquent\Model as EloquentModel;
use ProAI\Datamapper\Eloquent\Collection as EloquentCollection;
use ProAI\Datamapper\Contracts\Entity as EntityContract;
use ProAI\Datamapper\Support\Proxy;
use ProAI\Datamapper\Support\ProxyCollection;

abstract class Entity extends Model implements EntityContract
{
    /**
     * Build new instance from an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $eloquentModel
     * @return \ProAI\Datamapper\Support\Entity
     */
    public static function newFromEloquentModel(EloquentModel $eloquentModel)
    {
        $entity = new static;

        // get model data
        $dict = [
            'mapping' => $eloquentModel->getMapping(),
            'attributes' => $eloquentModel->getAttributes(),
            'relations' => $eloquentModel->getRelations()
        ];

        // attributes
        foreach ($dict['mapping']['attributes'] as $attribute => $column) {
            $entity->{$attribute} = $dict['attributes'][$column];
        }

        // embeddeds
        foreach ($dict['mapping']['embeddeds'] as $name => $embedded) {
            $entity->{$name} = $embedded['class']::newFromEloquentModel($eloquentModel, $name);
        }

        // relations
        foreach ($dict['mapping']['relations'] as $name => $relation) {
            if (! empty($dict['relations'][$name])) {
                $relationObject = $dict['relations'][$name]->toEntity();
            } else {
                if (in_array($relation['type'], ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
                    $relationObject = new ProxyCollection;
                } else {
                    $relationObject = new Proxy;
                }
            }
            
            $entity->{$name} = $relationObject;
        }

        return $entity;
    }

    /**
     * Convert an instance to an eloquent model object.
     *
     * @return \ProAI\Datamapper\\Eloquent\Model
     */
    public function toEloquentModel()
    {
        $class = get_mapped_model(static::class);

        $eloquentModel = new $class;

        // get model data
        $dict = [
            'mapping' => $eloquentModel->getMapping(),
            'attributes' => $eloquentModel->getAttributes(),
            'relations' => $eloquentModel->getRelations()
        ];

        // attributes
        foreach ($dict['mapping']['attributes'] as $attribute => $column) {
            if (! $eloquentModel->isGeneratedDate($column)) {
                $eloquentModel->setAttribute($column, $this->{$attribute});
            }
        }

        // embeddeds
        foreach ($dict['mapping']['embeddeds'] as $name => $embedded) {
            $embeddedObject = $this->{$name};

            if (! empty($embeddedObject)) {
                $embeddedObject->toEloquentModel($eloquentModel, $name);
            }
        }

        // relations
        foreach ($dict['mapping']['relations'] as $name => $relation) {
            $relationObject = $this->{$name};

            if (! empty($relationObject) && ! $relationObject instanceof \ProAI\Datamapper\Contracts\Proxy) {
                $value = ($relationObject instanceof \ProAI\Datamapper\Support\Collection)
                    ? EloquentCollection::newFromEntity($relationObject)
                    : EloquentModel::newFromEntity($relationObject);
                
                $eloquentModel->setRelation($name, $value);
            }
        }

        return $eloquentModel;
    }
}
