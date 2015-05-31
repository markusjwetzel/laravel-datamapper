<?php namespace Wetzel\Datamapper\Annotations\Relation;

use Wetzel\Datamapper\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class MorphMany extends Relation implements Annotation
{

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
    public $id = null;

    /**
     * @var string
     */
    public $localKey = null;
}