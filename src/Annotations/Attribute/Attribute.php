<?php namespace Wetzel\Datamapper\Annotations\Attribute;

use Wetzel\Datamapper\Annotations\Annotation;

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
}