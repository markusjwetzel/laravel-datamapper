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
    public $localKey;

    /**
     * @var string
     */
    public $foreignEntity;

    /**
     * @var string
     */
    public $foreignKey;

    /**
     * @var string
     */
    public $throughEntity;

    /**
     * @var string
     */
    public $throughKey;

    /**
     * @var string
     */
    public $pivotTable;

    /**
     * @var string
     */
    public $morphName;

    /**
     * @var string
     */
    public $morphType;

    /**
     * @var string
     */
    public $morphId;

    /**
     * @var boolean
     */
    public $inverse = false;

    /**
     * @var string
     */
    public $relation;
}