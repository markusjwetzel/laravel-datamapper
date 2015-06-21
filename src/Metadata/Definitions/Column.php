<?php

namespace ProAI\Datamapper\Metadata\Definitions;

class Column extends Definition
{
    /**
     * Valid keys.
     *
     * @var array
     */
    protected $keys = [
        'name' => null,
        'type' => null,
        'primary' => false,
        'index' => false,
        'unique' => false,
        'nullable' => false,
        'default' => null,
        'options' => [],
    ];
}
