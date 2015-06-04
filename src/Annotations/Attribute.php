<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Attribute implements Annotation
{
    /**
     * @var string
     */
    public $type;

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
     * @var string
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
}