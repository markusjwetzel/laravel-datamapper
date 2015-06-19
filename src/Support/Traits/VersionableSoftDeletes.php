<?php

namespace Wetzel\Datamapper\Support\Traits;

use Wetzel\Datamapper\Annotations as ORM;

trait VersionableSoftDeletes
{
    /**
     * @ORM\Column(type="dateTime")
     * @ORM\Versioned
     */
    protected $deletedAt;
}
