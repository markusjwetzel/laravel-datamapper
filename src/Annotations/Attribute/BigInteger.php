<?php namespace Wetzel\Datamapper\Annotations\Attribute;

use Wetzel\Datamapper\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class BigInteger extends Attribute implements Annotation
{
    /**
     * @var boolean
     */
    public $autoIncrement = false;

    /**
     * @var boolean
     */
    public $unsigned = false;
}