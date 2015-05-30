<?php namespace Wetzel/Datamapper/Annotations/Attribute;

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