<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Visible implements Annotation
{
    /**
     * @var array
     */
    public $value;
}