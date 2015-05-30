<?php namespace Wetzel\Datamapper\Metadata\Definitions;

class Column extends Definition {
    
    /**
     * Valid keys.
     * 
     * @var array
     */
    $keys = [
        'name' => null,
        'type' => null,
        'primary' => false,
        'index' => false,
        'unique' => false,
        'nullable' => false,
        'default' => null,
        'unsigned' => false,
        'options' => [],
    ];

}