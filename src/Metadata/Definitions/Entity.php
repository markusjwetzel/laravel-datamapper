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
        
        'softDeletes' => false,
        'timestamps' => false,
        'versionable' => false,

        'hidden' => [],
        'visible' => [],
        'fillable' => [],
        'guarded' => [],
        'touches' => [],

        'columns' => [],
        'attributes' => [],
        'embeddeds' => [],
        'relations' => [],
    ];

}