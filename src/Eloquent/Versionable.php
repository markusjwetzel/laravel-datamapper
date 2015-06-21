<?php

namespace ProAI\Datamapper\Eloquent;

use ProAI\Versioning\Versionable as VersionableTrait;

trait Versionable
{
    use VersionableTrait;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \ProAI\Datamapper\Versioning\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new VersioningBuilder($query);
    }
}
