<?php namespace Wetzel\DataMapper\Support\Traits;

use Wetzel\DataMapper\Annotations as ORM;

trait Versionable {
    
    /**
     * @ORM\Attribute('integer')
     */
    private $version;

    /**
     * Get the created at timestamp
     *
     * @return integer
     */
    public function version()
    {
        return $this->version;
    }

}