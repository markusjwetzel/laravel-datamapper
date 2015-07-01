<?php

namespace ProAI\Datamapper\Support\Traits;

use ProAI\Datamapper\Annotations as ORM;
use Carbon\Carbon;

trait VersionableSoftDeletes
{
    /**
     * @ORM\Column(type="dateTime", nullable=true)
     * @ORM\Versioned
     */
    protected $deletedAt;

    /**
     * @return \Carbon\Carbon
     */
    public function deletedAt()
    {
        return Carbon::instance($this->deletedAt->date());
    }
}
