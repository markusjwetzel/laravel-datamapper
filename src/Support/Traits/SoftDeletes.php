<?php

namespace ProAI\Datamapper\Support\Traits;

use ProAI\Datamapper\Annotations as ORM;

trait SoftDeletes
{
    /**
     * @ORM\Column(type="dateTime", nullable=true)
     */
    protected $deletedAt;
}
