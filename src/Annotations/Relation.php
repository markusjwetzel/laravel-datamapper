<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Relation implements Annotation
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $targetEntity;

    /**
     * @var string
     */
    public $morphName;

    /**
     * @var string
     */
    public $table;

    /**
     * @var string
     */
    public $throughEntity;

    /**
     * @var string
     */
    public $foreignKey;

    /**
     * @var string
     */
    public $otherKey;

    /**
     * @var string
     */
    public $localKey;

    /**
     * @var string
     */
    public $firstKey;

    /**
     * @var string
     */
    public $secondKey;

    /**
     * @var boolean
     */
    public $inverse = false;

    /**
     * @var string
     */
    public $morphType;

    /**
     * @var string
     */
    public $morphId;

    /**
     * @var string
     */
    public $relation;
}