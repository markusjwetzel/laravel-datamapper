<?php namespace Wetzel/DataMapper/Mapping;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class MorphTo extends Relation implements Annotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type = null;

    /**
     * @var string
     */
    public $id = null;
}