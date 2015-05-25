<?php namespace Wetzel/DataMapper/Annotations/Attribute;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Char extends Attribute implements Annotation
{
    /**
     * @var integer
     */
    public $length = null;
}