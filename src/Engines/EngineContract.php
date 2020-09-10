<?php

namespace CyberDuck\Search\Engines;


use CyberDuck\Search\AllBuilder;
use CyberDuck\Search\Builder;
use Illuminate\Database\Eloquent\Collection;

abstract class EngineContract
{
    /**
     * Update the given model in the index.
     *
     * @param  Collection  $models
     * @return void
     */
    abstract public function update($models);

    /**
     * Remove the given model from the index.
     *
     * @param  Collection  $models
     * @return void
     */
    abstract public function delete($models);

    /**
     * Remove the given models index
     *
     * @param $model
     * @return void
     */
    abstract public function deleteIndex($model);

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @return mixed
     */
    abstract public function search(Builder $builder);

    /**
     * Perform the given search on the engine for all indices
     *
     * @param AllBuilder $builder
     * @return mixed
     */
    abstract public function searchAll(AllBuilder $builder);

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @param $results
     * @return mixed
     */
    abstract public function mapAll(Builder $builder, $results);

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    abstract public function mapIds($results);

    /**
     * Map the given results to instances of the given model.
     *
     * @param  Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return Collection
     */
    abstract public function map(Builder $builder, $results, $model);

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    abstract public function getTotalCount($results);

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    abstract public function flush($model);

    /**
     * Get the results of the query as a Collection of primary keys.
     *
     * @param  Builder  $builder
     * @return \Illuminate\Support\Collection
     */
    public function keys(Builder $builder)
    {
        return $this->mapIds($this->search($builder));
    }

    /**
     * Get the results of the given query mapped onto models.
     *
     * @param  Builder  $builder
     * @return Collection
     */
    public function get(Builder $builder)
    {
        return $this->map(
            $builder, $this->search($builder), $builder->model
        );
    }

    /**
     * Get the results of the given query mapped onto models.
     *
     * @param AllBuilder $builder
     * @return Collection
     */
    public function getAll(AllBuilder $builder)
    {
        return $this->mapAll(
            $builder, $this->searchAll($builder)
        );
    }
}
