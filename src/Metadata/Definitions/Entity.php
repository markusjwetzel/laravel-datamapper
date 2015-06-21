<?php

namespace ProAI\Datamapper\Metadata\Definitions;

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
        'versionTable' => null,
        
        'softDeletes' => false,
        'timestamps' => false,
        
        'touches' => [],
        'with' => [],

        'columns' => [],
        'attributes' => [],
        'embeddeds' => [],
        'relations' => [],
    ];
}
