<?php namespace Wetzel\Datamapper\Annotations\Attribute;

use Wetzel\Datamapper\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class String extends Attribute implements Annotation
{
    /**
     * @var integer
     */
    public $length = null;
}