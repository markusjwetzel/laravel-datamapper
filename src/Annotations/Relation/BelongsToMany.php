<?php namespace Wetzel\Datamapper\Annotations\Relation;

use Wetzel\Datamapper\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class BelongsToMany extends Relation implements Annotation
{
    /**
     * @var string
     */
    public $table = null;

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