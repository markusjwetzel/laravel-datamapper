<?php namespace Wetzel/DataMapper/Mapping;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Table implements Annotation
{
    /**
     * @var string
     */
    public $value;
}