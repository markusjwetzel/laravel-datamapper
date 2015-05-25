<?php namespace Wetzel\DataMapper\Metadata\Definitions;

class PivotTable extends Definition {
    
    /**
     * Valid keys.
     * 
     * @var array
     */
    $keys = [
        'table' => null,
        'columns' => [],
    ];

}