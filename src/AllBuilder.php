<?php

namespace CyberDuck\Search;

use Illuminate\Database\Eloquent\Collection;

class AllBuilder extends Builder
{
    /**
     * @var array
     */
    public $indices = [];

    /**
     * @param $model
     * @param $query
     * @param $indices
     */
    public function __construct($model, $query, $indices)
    {
        $this->model = $model;
        $this->query = $query;
        $this->indices = $indices;
    }

    /**
     * @return Collection
     */
    public function get()
    {
        return app(EngineManager::class)->engine()->getAll($this);
    }
}
