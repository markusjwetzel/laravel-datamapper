<?php

namespace ProAI\Datamapper\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Presenter implements Annotation
{
    /**
     * @var string
     */
    public $class;
}
