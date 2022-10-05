<?php

namespace CyberDuck\Search;

class Search {
    /**
     * Perform a search against the model's indexed data.
     *
     * @param  string  $query
     *
     * @return Builder
     */
    public static function all($query = '', $indices = [])
    {
        return app(AllBuilder::class, [
            'model' => new static,
            'query' => $query,
            'indices' => $indices
        ]);
    }
}