<?php namespace Wetzel/DataMapper/Mapping;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class HasMany extends Relation implements Annotation
{
    /**
     * @var string
     */
    public $foreignKey = null;

    /**
     * @var string
     */
    public $localKey = null;
}