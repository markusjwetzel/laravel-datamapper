<?php namespace Wetzel\Datamapper\Metadata\Definitions;

class Entity extends Definition {
    
    /**
     * Valid keys.
     * 
     * @var array
     */
    protected $keys = [
        'class' => null,
        'table' => null,
        'timestamps' => false,
        'softdeletes' => false,
        'versionable' => false,
        'columns' => [],
        'attributes' => [],
        'embeddeds' => [],
        'relations' => [],
    ];

}