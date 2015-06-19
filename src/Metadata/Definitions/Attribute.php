<?php

namespace Wetzel\Datamapper\Metadata\Definitions;

class Attribute extends Definition
{
    /**
     * Valid keys.
     *
     * @var array
     */
    protected $keys = [
        'name' => null,
        'columnName' => null,
        'versioned' => false,
    ];
}
