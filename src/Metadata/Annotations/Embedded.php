<?php namespace Wetzel/DataMapper/Mapping;

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
    
    /**
     * @var string
     */
    public $columnPrefix;
}