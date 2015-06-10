<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class With implements Annotation
{
    /**
     * @var array
     */
    public $value;
}