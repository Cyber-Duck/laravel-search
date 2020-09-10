<?php

namespace CyberDuck\Search;

use Illuminate\Support\Collection;

class SearchCollection extends Collection {

    /**
     * @var array
     */
    private $results;

    /**
     * SearchCollection constructor.
     *
     * @param array $items
     * @param array $results
     */
    public function __construct($items = [], $results = [])
    {
        parent::__construct($items);
        $this->results = $results;
    }

    /**
     * @return mixed
     */
    public function totalHits()
    {
        return app(EngineManager::class)->engine()->getTotalCount($this->results);
    }
}