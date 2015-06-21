<?php

namespace ProAI\Datamapper\Support\Traits;

use ProAI\Datamapper\Annotations as ORM;

trait VersionableSoftDeletes
{
    /**
     * @ORM\Column(type="dateTime", nullable=true)
     * @ORM\Versioned
     */
    protected $deletedAt;
}
