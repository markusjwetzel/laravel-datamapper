<?php

namespace Wetzel\Datamapper\Eloquent;

use ProAI\Versioning\Versionable as VersionableTrait;

trait Versionable
{
    use VersionableTrait;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Wetzel\Datamapper\Versioning\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new VersioningBuilder($query);
    }
}
