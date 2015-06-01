<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Hidden implements Annotation
{
    /**
     * @var array
     */
    public $attributes;
}