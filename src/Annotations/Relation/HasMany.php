<?php namespace Wetzel/Datamapper/Annotations/Relation;

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