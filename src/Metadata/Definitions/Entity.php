<?php

namespace Wetzel\Datamapper\Metadata\Definitions;

class Entity extends Definition
{
    /**
     * Valid keys.
     *
     * @var array
     */
    protected $keys = [
        'class' => null,
        'morphClass' => null,
        'table' => null,
        
        'softDeletes' => false,
        'timestamps' => false,
        'versionable' => false,
        
        'touches' => [],
        'with' => [],

        'columns' => [],
        'attributes' => [],
        'embeddeds' => [],
        'relations' => [],
    ];
}
