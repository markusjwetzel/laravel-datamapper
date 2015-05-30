<?php namespace Wetzel\Datamapper\Support\Traits;

use Wetzel\Datamapper\Annotations as ORM;

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