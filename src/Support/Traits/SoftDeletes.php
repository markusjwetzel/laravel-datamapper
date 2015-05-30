<?php namespace Wetzel\Datamapper\Support\Traits;

use Wetzel\Datamapper\Annotations as ORM;
use Carbon\Carbon;

trait SoftDeletes {
    
    /**
     * @ORM\Attribute('timestamp')
     */
    private $deleted_at;

    /**
     * Get the created at timestamp
     *
     * @return Carbon
     */
    public function deletedAt()
    {
        return Carbon::instance($this->deleted_at);
    }

}