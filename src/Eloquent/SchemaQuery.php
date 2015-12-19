<?php

namespace ProAI\Datamapper\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentQueryBuilder;

class SchemaQuery
{
    /**
     * Root of schema.
     *
     * @var array
     */
    protected $root;

    /**
     * Query schema.
     *
     * @var array
     */
    protected $schema;

    /**
     * Constraints for schema.
     *
     * @var array
     */
    protected $constraints = [];

    /**
     * Result transformations.
     *
     * @var array
     */
    protected $transformations = [];

    /**
     * Eloquent query instance.
     *
     * @var array
     */
    protected $eloquentQuery;

    /**
     * Create a new schema query builder instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $eloquentQuery
     * @return void
     */
    public function __construct(EloquentQueryBuilder $eloquentQuery)
    {
        $this->eloquentQuery = $eloquentQuery;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @return array|null
     */
    public function get()
    {
        return $this->getResults('get');
    }

    /**
     * Execute the query and get the first result.
     *
     * @return array|null
     */
    public function first()
    {
        return $this->getResults('first');
    }

    /**
     * Prepare query for execution.
     *
     * @param string $method
     * @return array|null
     */
    protected function getResults($method)
    {
        if ($this->schema) {
            // set root constraints
            if (isset($this->constraints[$this->root])) {
                $this->constraints[$this->root]($this->eloquentQuery);
            }

            // set eager load constraints
            $this->eloquentQuery->setEagerLoads($this->parseRelationsFromSchema($this->schema, ''));

            // execute query
            $results = $this->eloquentQuery->$method();

            // transform to data transfer objects
            if ($results) {
                $dtos = $results->toDataTransferObject($this->root, $this->schema, $this->transformations);
                
                return [$this->root => $dtos];
            }
        }

        return null;
    }

    /**
     * Parse relations from schema.
     *
     * @param array $schema
     * @param string $path
     * @return void
     */
    protected function parseRelationsFromSchema(array $schema, $path='')
    {
        $results = [];

        foreach ($schema as $key => $value) {
            if (! is_numeric($key)) {
                // join relation
                if (substr($key, 0, 3) != '...') {
                    $childPath = ($path)
                        ? $path.'.'.$key
                        : $key;
                    $results[$childPath] = (isset($this->constraints[$this->root.'.'.$childPath]))
                        ? $this->constraints[$this->root.'.'.$childPath]
                        : function () {};
                } else {
                    $childPath = $path;
                }

                // recursive call
                $results = array_merge($results, $this->parseRelationsFromSchema($value, $childPath));
            }
        }

        return $results;
    }

    /**
     * Set the schema.
     *
     * @param  array  $schema
     * @return $this
     */
    public function schema(array $schema)
    {
        $this->root = key($schema);

        $this->schema = current($schema);

        return $this;
    }

    /**
     * Constraints for query relations.
     *
     * @param  array  $constraints
     * @return $this
     */
    public function constraints(array $constraints)
    {
        $this->constraints = array_merge($this->constraints, $constraints);

        return $this;
    }

    /**
     * Transform model attributes to data transfer attributes
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