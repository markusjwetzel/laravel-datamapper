<?php namespace Wetzel/DataMapper/Mapping;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Attribute implements Annotation
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $name;

    /**
     * @var integer
     */
    public $length;

    /**
     * @var integer
     */
    public $precision = 0;

    /**
     * @var integer
     */
    public $scale = 0;

    /**
     * @var boolean
     */
    public $nullable = false;

    /**
     * @var string
     */
    public $default = null;

    /**
     * @var boolean
     */
    public $unsigned = false;

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
}