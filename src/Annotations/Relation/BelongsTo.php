<?php namespace Wetzel/Datamapper/Annotations/Relation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class BelongsTo extends Relation implements Annotation
{
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