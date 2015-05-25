<?php namespace Wetzel\DataMapper;

class EntityManager {

    /**
     * Set an entity class.
     * 
     * @param string $class
     * @return Eloquent
     */
    public function class($class) {

    }

    /**
     * Create an entity object.
     * 
     * @param Entity $object
     * @return void
     */
    public function create($object) {

    }

    /**
     * Update an entity object.
     * 
     * @param Entity $object
     * @return void
     */
    public function update($object) {

    }

    /**
     * Delete an entity object.
     * 
     * @param Entity $object
     * @return void
     */
    public function delete($object) {

    }

    /**
     * Convert plain old php object to model.
     *
     * @return string
     */
    public function getEloquentEntity($object)
    {
        $reflectionObject = new ReflectionObject($object);

        foreach($reflectionObject->getProperties() as $reflectionProperty) {
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
        }
    }

    /**
     * Convert plain old php object to model.
     *
     * @return string
     */
    public function toArray($object)
    {
        $reflectionObject = new ReflectionObject($object);

        foreach($reflectionObject->getProperties() as $reflectionProperty) {
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
        }
    }

}