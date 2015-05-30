<?php namespace Wetzel\Datamapper\Metadata\Definitions;

class Class extends Definition {
    
    /**
     * Valid keys.
     * 
     * @var array
     */
    $keys = [
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