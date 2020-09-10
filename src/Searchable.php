<?php

namespace CyberDuck\Search;

use CyberDuck\Search\Jobs\MakeSearchable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

trait Searchable {

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootSearchable()
    {
        static::addGlobalScope(new SearchableScope);

        static::observe(new ModelObserver);

        (new static)->registerSearchableMacros();
    }

    /**
     * Get the value used to index the model.
     *
     * @return mixed
     */
    public function getSearchKey()
    {
        return $this->getKey();
    }

    /**
     * Make all instances of the model searchable.
     *
     * @param  int  $chunk
     * @return void
     */
    public static function makeAllSearchable($chunk = null)
    {
        $self = new static;

        $self->newQuery()
            ->orderBy($self->getKeyName())
            ->searchable($chunk);
    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public function shouldBeSearchable()
    {
        return true;
    }

    /**
     * Make the given model instance searchable.
     *
     * @return void
     */
    public function searchable()
    {
        $this->newCollection([$this])->searchable();
    }

    /**
     * Register the searchable macros.
     *
     * @return void
     */
    public function registerSearchableMacros()
    {
        $self = $this;

        BaseCollection::macro('searchable', function () use ($self) {
            $self->queueMakeSearchable($this);
        });

        BaseCollection::macro('unsearchable', function () use ($self) {
            $self->queueRemoveFromSearch($this);
        });
    }

    /**
     * Dispatch the job to make the given models searchable.
     *
     * @param  Collection  $models
     * @return void
     */
    public function queueMakeSearchable($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        if (! config('search.queue')) {
            return $models->first()->searchableUsing()->update($models);
        }

        dispatch((new MakeSearchable($models))
            ->onQueue($models->first()->syncWithSearchUsingQueue())
            ->onConnection($models->first()->syncWithSearchUsing()));
    }

    /**
     * Get the Scout engine for the model.
     *
     * @return mixed
     */
    public function searchableUsing()
    {
        return app(EngineManager::class)->engine();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return $this->toArray();
    }

    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return config('search.index_prefix').$this->getTable();
    }

    /**
     * Method to retrieve index settings
     *
     * @return bool|array
     */
    public function indexSettings()
    {
        return false;
    }

    /**
     * Method to retrieve index mapping properties
     *
     * @return bool|array
     */
    public function indexProperties()
    {
        return false;
    }

    /**
     * Remove all instances of the model from the search index.
     *
     * @return void
     */
    public static function removeAllFromSearch()
    {
        $self = new static;

        $self->searchableUsing()->flush($self);
    }

    /**
     * Dispatch the job to make the given models unsearchable.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function queueRemoveFromSearch($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        return $models->first()->searchableUsing()->delete($models);
    }

    /**
     * Remove the given model instance from the search index.
     *
     * @return void
     */
    public function unsearchable()
    {
        $this->newCollection([$this])->unsearchable();
    }

    /**
     * Perform a search against the model's indexed data.
     *
     * @param  string  $query
     *
     * @return Builder
     */
    public static function search($query = '')
    {
        return app(Builder::class, [
            'model' => new static,
            'query' => $query,
        ]);
    }

    /**
     * Get the key name used to index the model.
     *
     * @return mixed
     */
    public function getSearchKeyName()
    {
        return $this->getQualifiedKeyName();
    }

    /**
     * Get the requested models from an array of object IDs.
     *
     * @param Builder $builder
     * @param array $ids
     * @return mixed
     */
    public function getSearchModelsByIds(array $ids)
    {
        return $this->newQuery()->whereIn(
            $this->getSearchKeyName(), $ids
        )->get();
    }

    /**
     * Method to delete index
     */
    public static function deleteIndex()
    {
        $self = new static;

        $self->searchableUsing()->deleteIndex($self);
    }
}