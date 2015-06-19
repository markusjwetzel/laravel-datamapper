<?php

namespace Wetzel\Datamapper\Support\Traits;

use Wetzel\Datamapper\Annotations as ORM;
use Carbon\Carbon;

trait Timestamps
{
    /**
     * @ORM\Column(type="dateTime")
     */
    protected $createdAt;
    
    /**
     * @ORM\Column(type="dateTime")
     */
    protected $updatedAt;
}
