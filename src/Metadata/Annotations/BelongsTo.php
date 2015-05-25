<?php namespace Wetzel/DataMapper/Mapping;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class BelongsTo implements Annotation
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $foreignKey = null;

    /**
     * @var string
     */
    public $otherKey = null;

    /**
     * @var string
     */
    public $relation = null;
}