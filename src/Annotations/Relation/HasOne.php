<?php namespace Wetzel/DataMapper/Mapping;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class HasOne extends Relation implements Annotation
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