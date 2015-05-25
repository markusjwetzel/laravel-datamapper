<?php namespace Wetzel\DataMapper\Metadata\Definitions;

class Relation extends Definition {
    
    /**
     * Valid keys.
     * 
     * @var array
     */
    $keys = [
        'name' => null,
        'type' => null,
        'relatedClass' => null,
        'pivotTable' => new PivotTable;
        'options' => [],
    ];

}