<?php namespace Wetzel\DataMapper\Support\Traits;

use Wetzel\DataMapper\Annotations as ORM;
use Carbon\Carbon;

trait Timestamps {
    
    /**
     * @ORM\Attribute('timestamp')
     */
    private $created_at;
    
    /**
     * @ORM\Attribute('timestamp')
     * @Versioned
     */
    private $updated_at;

    /**
     * Get the created at timestamp
     *
     * @return Carbon
     */
    public function createdAt()
    {
        return Carbon::instance($this->created_at);
    }

    /**
     * Get the created at timestamp
     *
     * @return Carbon
     */
    public function updatedAt()
    {
        return Carbon::instance($this->updated_at);
    }

}