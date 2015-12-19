<?php

namespace ProAI\Datamapper\Eloquent;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as BaseBuilder;
use Exception;

class Builder extends BaseBuilder
{
    /**
     * The return type value.
     *
     * @var string
     */
    protected $returnType;

    /**
     * Constant representing entities return type.
     *
     * @var string
     */
    const RETURN_TYPE_DATAMAPPER = 'return-datamapper-objects';

    /**
     * Constant representing eloquent models return type.
     *
     * @var string
     */
    const RETURN_TYPE_ELOQUENT = 'return-eloquent-objects';

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $return
     * @return void
     */
    public function __construct(QueryBuilder $query, $returnType=Builder::RETURN_TYPE_ELOQUENT)
    {
        // validate return type
        switch($returnType) {
            case Builder::RETURN_TYPE_DATAMAPPER:
                break;
            case Builder::RETURN_TYPE_ELOQUENT:
                break;
            default:
                throw new Exception('Invalid return type');
        }

        $this->query = $query;

        $this->returnType = $returnType;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function get($columns = array('*'))
    {
        $results = parent::get($columns);

        switch($this->returnType) {
            case Builder::RETURN_TYPE_DATAMAPPER:
                return $results->toDatamapperObject();
            case Builder::RETURN_TYPE_ELOQUENT:
                return $results;
        }
    }
}