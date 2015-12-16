<?php

namespace ProAI\Datamapper\Eloquent;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Exception;
use Closure;

class Builder extends BaseBuilder
{
    /**
     * The return type value.
     *
     * @var string
     */
    protected $returnType;

    /**
     * Query schema.
     *
     * @var string
     */
    protected $schema;

    /**
     * Result transformations.
     *
     * @var array
     */
    protected $transformations = [];

    /**
     * Constant representing entities return type.
     *
     * @var string
     */
    const RETURN_TYPE_ENTITIES = 'return.entities';

    /**
     * Constant representing dtos return type.
     *
     * @var string
     */
    const RETURN_TYPE_DTOS = 'return.dtos';

    /**
     * Constant representing eloquent models return type.
     *
     * @var string
     */
    const RETURN_TYPE_ELOQUENT = 'return.eloquent';

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
            case Builder::RETURN_TYPE_ENTITIES:
                break;

            case Builder::RETURN_TYPE_DTOS:
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
            case Builder::RETURN_TYPE_ENTITIES:
                return $results->toEntity();

            case Builder::RETURN_TYPE_DTOS:
                // load relations from schema
                if (! isset($this->schema)) {
                    throw new Exception('No schema defined.');
                }
                $schema = current($this->schema);
                $this->withRelationsFromSchema($schema);

                // get dtos
                return [key($this->schema) => $results->toDataTransferObject($schema, $this->transformations)];

            case Builder::RETURN_TYPE_ELOQUENT:
                return $results;
        }
    }

    /**
     * Load relations from schema.
     *
     * @param string $path
     * @return void
     */
    protected function withRelationsFromSchema($schema, $path='')
    {
        foreach ($schema as $key => $value) {
            if (! is_numeric($key)) {
                // join relation
                if (substr($key, 0, 3) != '...') {
                    $path .= '.'.$key;
                    $this->with($path);
                }

                // recursive call
                $this->withRelationsFromSchema($value, $path);
            }
        }
    }

    /**
     * Set the schema.
     *
     * @param  array  $schema
     * @return $this
     */
    public function schema(array $schema)
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Set the transformations.
     *
     * @param  array  $transformations
     * @return $this
     */
    public function transform($transformations)
    {
        $this->transformations = array_merge($this->transformations, $transformations);

        return $this;
    }
}
