<?php

namespace Wetzel\Datamapper\Metadata\Definitions;

class Relation extends Definition
{
    /**
     * Valid keys.
     *
     * @var array
     */
    protected $keys = [
        'name' => null,
        'type' => null,
        'foreignEntity' => null,
        'pivotTable' => null,
        'options' => [],
    ];
}
