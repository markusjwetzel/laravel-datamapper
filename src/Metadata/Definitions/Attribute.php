<?php

namespace ProAI\Datamapper\Metadata\Definitions;

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
