<?php

namespace ProAI\Datamapper\Support;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use ProAI\Datamapper\Contracts\ValueObject as ValueObjectContract;
use Exception;

abstract class ValueObject extends Model implements ValueObjectContract
{
    /**
     * Compare two value objects.
     *
     * @param \ProAI\Datamapper\Contracts\ValueObject $valueObject
     * @return boolean
     */
    public function equals(ValueObjectContract $valueObject)
    {
        foreach (get_object_vars($this) as $name => $value) {
            if ($this->{$name} !== $valueObject->{$name}) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the object as a string
     *
     * @return string
     */
    public function toString()
    {
        $vars = get_object_vars($this);
        
        if (count($vars) == 1) {
            return $vars[0];
        } else {
            throw new Exception('Cannot convert value object to string, because there is more than one property');
        }
    }

    /**
     * Return the object as a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
    
    /**
     * Build new instance from an eloquent model object.
     *
     * @param \Illuminate\Database\Eloquent\Model $eloquentModel
     * @param array $name
     * @return \ProAI\Datamapper\Support\ValueObject
     */
    public static function newFromEloquentModel(EloquentModel $eloquentModel, $name)
    {
        $valueObject = new static;

        // get model data
        $dict = [
            'mapping' => $eloquentModel->getMapping(),
            'attributes' => $eloquentModel->getAttributes()
        ];

        foreach ($dict['mapping']['embeddeds'][$name]['attributes'] as $attribute => $column) {
            $valueObject->{$attribute} = $dict['attributes'][$column];
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

        foreach ($dict['mapping']['embeddeds'][$name]['attributes'] as $attribute => $column) {
            $eloquentModel->setAttribute($column, $this->{$attribute});
        }
    }
}
