<?php namespace Wetzel/DataMapper/Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Relation implements Annotation
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var string
     */
    public $related;

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
    public $table = null;

    /**
     * @var string
     */
    public $through;

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
    public $localKey = null;

    /**
     * @var string
     */
    public $firstKey = null;

    /**
     * @var string
     */
    public $secondKey = null;

    /**
     * @var integer
     */
    public $inverse = false;

    /**
     * @var string
     */
    public $id = null;

    /**
     * @var string
     */
    public $relation = null;
}