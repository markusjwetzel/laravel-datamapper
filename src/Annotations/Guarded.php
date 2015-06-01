<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Guarded implements Annotation
{
    /**
     * @var array
     */
    public $attributes;
}