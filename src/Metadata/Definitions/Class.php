<?php namespace Wetzel\DataMapper\Metadata\Definitions;

class Class extends Definition {
    
    /**
     * Valid keys.
     * 
     * @var array
     */
    $keys = [
        'table' => null,
        'timestamps' => false,
        'softdeletes' => false,
        'versionable' => false,
        'columns' => [],
        'attributes' => new Attributes,
        'embeddeds' => [],
        'relations' => [],
    ];

}