<?php

namespace Wetzel\Datamapper\Annotations;

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
    public $relatedEntity;

    /**
     * @var string
     */
    public $throughEntity;

    /**
     * @var string
     */
    public $localKey;

    /**
     * @var string
     */
    public $localForeignKey;

    /**
     * @var string
     */
    public $relatedForeignKey;

    /**
     * @var string
     */
    public $throughForeignKey;

    /**
     * @var string
     */
    public $localPivotKey;

    /**
     * @var string
     */
    public $relatedPivotKey;

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
