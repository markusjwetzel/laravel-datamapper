<?php

namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Column implements Annotation
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $name;

    /**
     * @var boolean
     */
    public $primary = false;

    /**
     * @var boolean
     */
    public $unique = false;

    /**
     * @var boolean
     */
    public $index = false;

    /**
     * @var boolean
     */
    public $nullable = false;

    /**
     * @var mixed
     */
    public $default;
    
    /**
     * @var boolean
     */
    public $unsigned = false;

    /**
     * @var integer
     */
    public $length = 255;

    /**
     * @var integer
     */
    public $scale = 8;

    /**
     * @var integer
     */
    public $precision = 2;

    /**
     * @var boolean
     */
    public $autoIncrement = false;

    /**
     * @var boolean
     */
    public $versioned = false;
}
