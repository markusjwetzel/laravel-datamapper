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
     * @ORM\Id
     * @ORM\Column(type="integer", unsigned=true, primary=true)
     * @ORM\Versioned
     */
    protected $version;

    /**
     * @return string
     */
    public function latestVersion()
    {
        return $this->latestVersion->version();
    }

    /**
     * @return string
     */
    public function version()
    {
        return $this->version->version();
    }
}
