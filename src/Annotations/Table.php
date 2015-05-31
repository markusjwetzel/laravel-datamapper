<?php namespace Wetzel\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Table implements Annotation
{
    /**
     * @var string
     */
    public $name;
}