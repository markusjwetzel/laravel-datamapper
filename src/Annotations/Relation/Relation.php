<?php namespace Wetzel\Datamapper\Annotations\Relation;

use Wetzel\Datamapper\Annotations\Annotation;

abstract class Relation implements Annotation
{
    /**
     * @var string
     */
    public $related;
}