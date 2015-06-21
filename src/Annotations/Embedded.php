<?php

namespace ProAI\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Embedded implements Annotation
{
    /**
     * @var string
     */
    public $class;

    /**
     * @var mixed
     */
    public $columnPrefix;
}
