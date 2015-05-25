<?php namespace Wetzel/DataMapper/Mapping;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class HasManyThrough implements Annotation
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $through;

    /**
     * @var string
     */
    public $firstKey = null;

    /**
     * @var string
     */
    public $secondKey = null;
}