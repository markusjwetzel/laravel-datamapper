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
     * Root of schema.
     *
     * @var array
     */
    protected $schemaRoot;

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
    protected $schemaConstraints = [];

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
        // prepare schema relations for DTOs
        if ($this->returnType == Builder::RETURN_TYPE_DTOS) {
            // load relations from schema
            if (! isset($this->schema)) {
                throw new Exception('No schema defined.');
            }
            $this->eagerLoad = $this->parseRelationsFromSchema($this->schema);
        }

        $results = parent::get($columns);

        switch($this->returnType) {
            case Builder::RETURN_TYPE_ENTITIES:
                return $results->toEntity();

            case Builder::RETURN_TYPE_DTOS:
                // get dtos
                return [$this->schemaRoot => $results->toDataTransferObject($this->schema, $this->transformations)];

            case Builder::RETURN_TYPE_ELOQUENT:
                return $results;
        }
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static|null
     */
    public function first($columns = ['*'])
    {
        $results = $this->take(1)->get($columns);

        return ($this->returnType == Builder::RETURN_TYPE_DTOS)
            ? [$this->schemaRoot => $results[$this->schemaRoot]->first()]
            : $results->first();
    }

    /**
     * Parse relations from schema.
     *
     * @param string $path
     * @return void
     */
    protected function parseRelationsFromSchema($schema, $path='')
    {
        $results = [];

        foreach ($schema as $key => $value) {
            if (! is_numeric($key)) {
                // join relation
                if (substr($key, 0, 3) != '...') {
                    $childPath = ($path)
                        ? $path.'.'.$key
                        : $key;
                    $results[$childPath] = (isset($this->schemaConstraints[$this->schemaRoot.'.'.$childPath]))
                        ? $this->schemaConstraints[$this->schemaRoot.'.'.$childPath]
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
        $this->schemaRoot = key($schema);

        $this->schema = current($schema);

        return $this;
    }

    /**
     * Constraints for query relations.
     *
     * @param  array  $constraints
     * @return $this
     */
    public function constraints($constraints)
    {
        $this->schemaContraints = array_merge($this->schemaConstraints, $constraints);

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
        foreach($transformations as $key => $transformation) {
            $parts = explode(".", $key);

            if ($parts[0] == $this->schemaRoot) {
                unset($parts[0]);
            }

            $key = implode(".", $parts);

            $this->transformations[$key] = $transformation;
        }

        return $this;
    }
}
