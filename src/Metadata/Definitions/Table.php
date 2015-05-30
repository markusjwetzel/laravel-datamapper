<?php namespace Wetzel\Datamapper\Metadata\Definitions;

class Table extends Definition {
    
    /**
     * Valid keys.
     * 
     * @var array
     */
    $keys = [
        'name' => null,
        'columns' => [],
    ];

}