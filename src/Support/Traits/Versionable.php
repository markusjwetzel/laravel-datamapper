<?php

namespace Wetzel\Datamapper\Support\Traits;

use Wetzel\Datamapper\Annotations as ORM;

trait Versionable
{
    /**
     * @ORM\Column(type="integer", unsigned=true)
     */
    protected $latestVersion;

    /**
     * @ORM\Column(type="integer", unsigned=true, primary=true)
     * @ORM\Versioned
     */
    protected $version;
}
