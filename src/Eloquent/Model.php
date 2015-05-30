<?php namespace Wetzel\Datamapper\Eloquent;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use ReflectionClass;

class Model extends EloquentModel {

    /**
     * Convert model to plain old php object.
     *
     * @return string
     */
    public function toObject()
    {
        $reflectionClass = new ReflectionClass($this->class);

        $object = $reflectionClass->newInstanceWithoutConstructor();

        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            // convert attributes
            if (in_array($name, $this->attributes)) {
                $value = $this->attributes[$name];
            }

            // convert embeddeds
            elseif (in_array($name, $this->embeddeds)) {
                $value = $this->getEmbeddedObject($this->embeddeds[$name]);
            }

            // convert relations
            elseif (method_exists($this, $name)) {
                // todo
                $value = $this->getRelationObject($this->embeddeds[$name]);
            }

            // property not found
            else {
                // todo
                throw Exception;
            }

            $reflectionProperty->setValue($object, $value);
        }
    }


    /**
     * Create an instance of an embedded object.
     *
     * @param string $class
     * @return object
     */
    protected function getEmbeddedObject($class) {
        $reflectionClass = new ReflectionClass($class);

        $object = $reflectionClass->newInstanceWithoutConstructor();

        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            if (in_array($name, $this->attributes)) {
                $reflectionProperty->setValue($object, $this->attributes[$name]);
            }
        }

        return $object;
    }

    /**
     * Create an instance of a related object.
     *
     * @param string $class
     * @return object
     */
    protected function getRelationObject($class) {
        $reflectionClass = new ReflectionClass($class);

        $object = $reflectionClass->newInstanceWithoutConstructor();

        foreach($reflectionClass->getProperties() as $reflectionProperty) {
            $name = $reflectionProperty->getName();

            if (in_array($name, $this->attributes)) {
                $reflectionProperty->setValue($object, $this->attributes[$name]);
            }
        }

        return $object;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (isset($this->table)) return $this->table;

        $className = array_slice(explode('/',str_replace('\\', '/', get_class($this))), 2);

        // delete last entry if entry is equal to the next to last entry
        if (count($className) >= 2 && end($className) == prev($className)) {
            array_pop($className);
        }

        $classBasename = array_pop($className);

        return strtolower(implode('_',array_merge($className, preg_split('/(?<=\\w)(?=[A-Z])/', $classBasename))));
    }

}