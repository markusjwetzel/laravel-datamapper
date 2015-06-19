<?php

namespace Wetzel\Datamapper\Support;

use Wetzel\Datamapper\Eloquent\Model as EloquentModel;
use Wetzel\Datamapper\Eloquent\Collection as EloquentCollection;
use Wetzel\Datamapper\Contracts\Entity as EntityContract;

abstract class Entity extends Model implements EntityContract
{
    /**
     * Build new instance from an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $eloquentModel
     * @return \Wetzel\Datamapper\Support\Entity
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
                $relationObject = new Proxy;
            }
            
            $entity->{$name} = $relationObject;
        }

        return $entity;
    }

    /**
     * Convert an instance to an eloquent model object.
     *
     * @return \Wetzel\Datamapper\\Eloquent\Model
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

            $embeddedObject->toEloquentModel($eloquentModel, $name);
        }

        // relations
        foreach ($dict['mapping']['relations'] as $name => $relation) {
            $relationObject = $this->{$name};

            if (! empty($relationObject) && ! $relationObject instanceof \Wetzel\Datamapper\Contracts\Proxy) {
                $value = ($relationObject instanceof \Wetzel\Datamapper\Support\Collection)
                    ? EloquentCollection::newFromEntity($relationObject)
                    : EloquentModel::newFromEntity($relationObject);
                
                $eloquentModel->setRelation($name, $value);
            }
        }

        return $eloquentModel;
    }
}
