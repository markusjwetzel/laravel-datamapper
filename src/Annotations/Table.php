<?php

namespace ProAI\Datamapper\Annotations;

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
