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
        'relatedEntity' => null,
        'pivotTable' => null,
        'options' => [],
    ];
}
