<?php namespace Wetzel/Datamapper/Annotations/Attribute;

abstract class Attribute implements Annotation
{
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
    public $default = null;

    /**
     * @var boolean
     */
    public $unsigned = false;
}