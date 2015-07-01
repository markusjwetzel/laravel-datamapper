<?php

namespace ProAI\Datamapper\Support\Traits;

use ProAI\Datamapper\Annotations as ORM;
use Carbon\Carbon;

trait VersionableTimestamps
{
    /**
     * @ORM\Column(type="dateTime")
     */
    protected $createdAt;
    
    /**
     * @ORM\Column(type="dateTime")
     * @ORM\Versioned
     */
    protected $updatedAt;

    /**
     * @return \Carbon\Carbon
     */
    public function createdAt()
    {
        return Carbon::instance($this->createdAt->date());
    }

    /**
     * @return \Carbon\Carbon
     */
    public function updatedAt()
    {
        return Carbon::instance($this->updatedAt->date());
    }
}
