<?php namespace Wetzel\Datamapper\Metadata\Definitions;

class EmbeddedClass extends Definition {
    
    /**
     * Valid keys.
     * 
     * @var array
     */
    $keys = [
        'name' => null,
        'embeddedClass' => null,
        'attributes' => [],
    ];

}