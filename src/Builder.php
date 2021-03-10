<?php

namespace CyberDuck\Search;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Traits\Macroable;

class Builder
{
    use Macroable;

    /**
     * The model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $model;

    /**
     * The query expression.
     *
     * @var string
     */
    public $query;

    /**
     * Optional callback before search execution.
     *
     * @var string
     */
    public $callback;

    /**
     * The custom index specified for the search.
     *
     * @var string
     */
    public $index;

    /**
     * The "where" constraints added to the query.
     *
     * @var array
     */
    public $wheres = [];

    /**
     * @var array
     */
    public $ranges = [];

    /**
     * The "limit" that should be applied to the search.
     *
     * @var int
     */
    public $limit;

    /**
     * The "limit" that should be applied to the search.
     *
     * @var int
     */
    public $offset;

    /**
     * The "order" that should be applied to the search.
     *
     * @var array
     */
    public $sort = null;

    /**
     * Fields to use for search
     *
     * @var array
     */
    public $fields = [];

    /**
     * Create a new search builder instance.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $query
     * @param \Closure $callback
     * @param bool $softDelete
     * @return void
     */
    public function __construct($model, $query)
    {
        $this->model = $model;
        $this->query = $query;
    }

    /**
     * Specify a custom index to perform this search on.
     *
     * @param string $index
     * @return $this
     */
    public function within($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * Method to set fields to use for search
     *
     * @param $fields
     * @return $this
     */
    public function fields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Add a constraint to the search query.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function where($field, $value)
    {
        $this->wheres[$field] = $value;

        return $this;
    }

    /**
     * Add a range for a single field with one or more operators
     *
     * @param string $field
     * @param array $ranges
     *
     * @return $this
     */
    public function ranges($field, array $ranges)
    {
        $validOperators = ['gt', 'gte', 'lt', 'lte'];
        $selectedOperators = array_keys($ranges);

        foreach ($selectedOperators as $selectedOperator) {
            $isValidOperator = in_array($selectedOperator, $validOperators);
            if ($isValidOperator) {
                continue;
            }

            throw new \InvalidArgumentException("Invalid operator {$selectedOperator}");
        }

        $this->ranges[$field] = array_merge(
            $this->ranges[$field] ?? [],
            $ranges
        );

        return $this;
    }

    /**
     * Set the "limit" for the search query.
     *
     * @param int $limit
     * @return $this
     */
    public function take($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set the "limit" for the search query.
     *
     * @param $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Add an "order" for the search query.
     *
     * @param $sort
     * @return $this
     */
    public function sort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get the keys of search results.
     *
     * @return \Illuminate\Support\Collection
     */
    public function keys()
    {
        return $this->engine()->keys($this);
    }

    /**
     * Get the first result from the search.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function first()
    {
        return $this->get()->first();
    }

    /**
     * Get the results of the search.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get()
    {
        return $this->engine()->get($this);
    }

    /**
     * Get the engine that should handle the query.
     *
     * @return mixed
     */
    protected function engine()
    {
        return $this->model->searchableUsing();
    }
}
