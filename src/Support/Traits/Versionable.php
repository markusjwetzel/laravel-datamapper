<?php

namespace ProAI\Datamapper\Support\Traits;

use ProAI\Datamapper\Annotations as ORM;

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
