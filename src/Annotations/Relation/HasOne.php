<?php namespace Wetzel/Datamapper/Annotations/Relation;

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