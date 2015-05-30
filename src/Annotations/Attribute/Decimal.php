<?php namespace Wetzel/Datamapper/Annotations/Attribute;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Decimal extends Attribute implements Annotation
{
    /**
     * @var integer
     */
    public $precision;

    /**
     * @var integer
     */
    public $scale;
}