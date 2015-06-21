<?php

namespace ProAI\Datamapper\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ProAI\Datamapper\EntityManager
 */
class EntityManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'datamapper.entitymanager';
    }
}
