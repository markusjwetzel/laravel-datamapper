<?php namespace Wetzel\Datamapper\Support;

use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class Entity extends Model {

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
        foreach($dict['mapping']['attributes'] as $attribute) {
            $entity->{$attribute} = $dict['attributes'][$attribute];
        }

        // embeddeds
        foreach($dict['mapping']['embeddeds'] as $name => $embedded) {
            $entity->{$name} = $embedded['class']::newFromEloquentModel($eloquentModel, $name);
        }

        // relations
        foreach($dict['mapping']['relations'] as $name => $relation) {
            $entity->{$name} = ( ! empty($dict['relations'][$name]))
                ? $dict['relations'][$name]->toEntity()
                : null;
        }

        return $entity;
    }

    /**
     * Convert an instance to an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $eloquentModel
     * @return \Wetzel\Datamapper\\Eloquent\Model
     */
    public function toEloquentModel(EloquentModel $eloquentModel)
    {
        // get model data
        $dict = [
            'mapping' => $eloquentModel->getMapping(),
            'attributes' => $eloquentModel->getAttributes(),
            'relations' => $eloquentModel->getRelations()
        ];

        // attributes
        foreach($dict['mapping']['attributes'] as $attribute) {
            $eloquentModel->setAttribute($attribute, $this->{$attribute});
        }

        // embeddeds
        foreach($dict['mapping']['embeddeds'] as $name => $embedded) {
            $embeddedObject = $this->{$name};

            $embeddedObject->toEloquentModel($eloquentModel, $name);
        }

        // relations
        foreach($dict['mapping']['relations'] as $name => $relation) {
            $relationObject = $this->{$name};

            if ( ! empty($relationObject)) {
                $class = ($relationObject instanceof \Wetzel\Datamapper\Eloquent\Collection)
                    ? '\Wetzel\Datamapper\Eloquent\Collection'
                    : '\Wetzel\Datamapper\Eloquent\Model';

                $eloquentModel->setRelation($name, $class::newFromEntity($relationObject)); // or newFromEloquentModel??
            }
        }

        return $eloquentModel;
    }

}