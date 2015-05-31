<?php namespace Wetzel\DataMapper;

use BadMethodCallException;
use InvalidArgumentException;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder {

    /**
     * The base query builder instance.
     *
     * @var \Illuminate\Database\Query\Builder
     */
    protected $query;

    /**
     * The model being queried.
     *
     * @var \Wetzel\DataMapper\Model
     */
    protected $model;

    /**
     * The methods that should be returned from query builder.
     *
     * @var array
     */
    protected $passthru = array(
        'toSql', 'lists', 'insert', 'insertGetId', 'pluck', 'count',
        'min', 'max', 'avg', 'sum', 'exists', 'getBindings',
    );

    /**
     * Create a new Eloquent query builder instance.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return void
     */
    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    /**
     * Set the table which the query is targeting from entity class.
     *
     * @param  string  $classname
     * @return $this
     */
    public function class($classname)
    {
        if (class_exists($classname)) {
            $this->from = $this->getTable($classname);
        } else {
            throw new InvalidArgumentException("Classname not found.");
        }

        return $this;
    }

    /**
     * Set the table which the query is targeting from entity class.
     *
     * @param  Object  $object
     * @return $this
     */
    public function object($object)
    {
        $classname = get_class($object);

        $this->entity = $object;

        $this->from = $this->getTable($classname);

        return $this;
    }

    /**
     * Get all of the models from the database.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function all($columns = array('*'))
    {
        $instance = new static;

        return $instance->newQuery()->get($columns);
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Support\Collection|static|null
     */
    public static function find($id, $columns = array('*'))
    {
        return static::query()->find($id, $columns);
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string  $column
     * @param  string  $key
     * @return array
     */
    public function lists($column, $key = null)
    {
        $results = $this->query->lists($column, $key);

        // If the model has a mutator for the requested column, we will spin through
        // the results and mutate the values so that the mutated version of these
        // columns are returned as you would expect from these Eloquent models.
        if ($this->model->hasGetMutator($column))
        {
            foreach ($results as $key => &$value)
            {
                $fill = array($column => $value);

                $value = $this->model->newFromBuilder($fill)->$column;
            }
        }

        return $results;
    }

    /**
     * Paginate the given query.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'])
    {
        $total = $this->query->getCountForPagination();

        $this->query->forPage(
            $page = Paginator::resolveCurrentPage(),
            $perPage = $perPage ?: $this->model->getPerPage()
        );

        return new LengthAwarePaginator($this->get($columns), $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    /**
     * Being querying a model with eager loading.
     *
     * @param  array|string  $relations
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function with($relations)
    {
        if (is_string($relations)) $relations = func_get_args();

        $instance = new static;

        return $instance->newQuery()->with($relations);
    }

    /**
     * Insert a new record into the database.
     *
     * @param  mixed  $object
     * @return bool
     */
    public function insert($object)
    {
        if (is_array($object)) return parent::insert($object);
    }

    /**
     * Update a record in the database.
     *
     * @param  mixed  $object
     * @return int
     */
    public function update($object)
    {
        if (is_array($object)) return parent::insert($object);
    }

    /**
     * Delete a record from the database.
     *
     * @param  mixed  $object
     * @return int
     */
    public function delete($object = null)
    {

    }

    /**
     * Dynamically handle calls into the query instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $result = call_user_func_array(array($this->query, $method), $parameters);

        return in_array($method, $this->passthru) ? $result : $this;
    }

}
