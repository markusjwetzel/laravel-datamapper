<?php namespace Wetzel\Datamapper\Annotations\Relation;

use Wetzel\Datamapper\Annotations\Annotation;

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