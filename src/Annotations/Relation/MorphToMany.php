<?php namespace Wetzel/Datamapper/Annotations/Relation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class MorphToMany extends Relation implements Annotation
{
    /**
     * @var string
     */
    public $name;

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
     * @var integer
     */
    public $inverse = false;
}