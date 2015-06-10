<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Touches implements Annotation
{
    /**
     * @var array
     */
    public $value;
}