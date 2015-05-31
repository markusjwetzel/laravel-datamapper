<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Embedded implements Annotation
{
    /**
     * @var string
     */
    public $class;
}