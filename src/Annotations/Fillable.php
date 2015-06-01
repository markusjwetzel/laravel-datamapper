<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Fillable implements Annotation
{
    /**
     * @var array
     */
    public $attributes;
}