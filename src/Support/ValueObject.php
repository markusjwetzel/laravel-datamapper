<?php

namespace Wetzel\Datamapper\Support;

use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class ValueObject extends Model
{
    /**
     * Compare two value objects.
     *
     * @param \Wetzel\Datamapper\Support\ValueObject $valueObject
     * @return boolean
     */
    public function equals(ValueObject $valueObject)
    {
        foreach (get_object_vars($this) as $name => $value) {
            if ($this->{$name} !== $valueObject->{$name}) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * Build new instance from an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $eloquentModel
     * @param array $name
     * @return \Wetzel\Datamapper\Support\ValueObject
     */
    public static function newFromEloquentModel(EloquentModel $eloquentModel, $name)
    {
        $valueObject = new static;

        // get model data
        $dict = [
            'mapping' => $eloquentModel->getMapping(),
            'attributes' => $eloquentModel->getAttributes()
        ];

        foreach ($dict['mapping']['embeddeds'][$name]['attributes'] as $attribute) {
            $valueObject->{$attribute} = $dict['attributes'][$attribute];
        }

        return $valueObject;
    }

    /**
     * Convert an instance to an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $eloquentModel
     * @param array $name
     * @return void
     */
    public function toEloquentModel(EloquentModel &$eloquentModel, $name)
    {
        // get model data
        $dict = [
            'mapping' => $eloquentModel->getMapping()
        ];

        foreach ($dict['mapping']['embeddeds'][$name]['attributes'] as $attribute) {
            $eloquentModel->setAttribute($attribute, $this->{$attribute});
        }
    }
}
