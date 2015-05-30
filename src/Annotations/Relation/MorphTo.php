<?php namespace Wetzel/Datamapper/Annotations/Relation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class MorphTo extends Relation implements Annotation
{
    /**
     * @var string
     */
    public $related = null;

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
}