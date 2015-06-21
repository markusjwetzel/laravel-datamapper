<?php

namespace ProAI\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Entity implements Annotation
{
    /**
     * @var string
     */
    public $morphClass;

    /**
     * @var array
     */
    public $touches;

    /**
     * @var array
     */
    public $with;
}
