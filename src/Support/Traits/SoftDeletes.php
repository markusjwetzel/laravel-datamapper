<?php

namespace Wetzel\Datamapper\Support\Traits;

use Wetzel\Datamapper\Annotations as ORM;

trait SoftDeletes
{
    /**
     * @ORM\Column(type="dateTime", nullable=true)
     */
    protected $deletedAt;
}
