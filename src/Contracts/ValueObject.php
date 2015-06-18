<?php

namespace Wetzel\Datamapper\Contracts;

interface ValueObject extends Model
{
    /**
     * Compare two value objects.
     *
     * @param \Wetzel\Datamapper\Support\ValueObject $valueObject
     * @return boolean
     */
    public function equals(ValueObject $valueObject);
}
