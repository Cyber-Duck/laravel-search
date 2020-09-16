<?php

namespace CyberDuck\Search\Engines;

use App\Models\Post;
use CyberDuck\Search\AllBuilder;
use CyberDuck\Search\Builder;
use CyberDuck\Search\SearchCollection;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ElasticSearchEngine extends EngineContract
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $indexPrefix;

    public function __construct(Client $client, string $indexPrefix)
    {
        $this->client = $client;
        $this->indexPrefix = $indexPrefix;
    }

    /**
     * Update the given model in the index.
     *
     * @param  Collection  $models
     * @return void
     */
    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $config = $models->first();
        $type = $config->searchableAs();
        $index = "$this->indexPrefix-$type";

        $params = [
            'index' => $index
        ];

        if (!$this->client->indices()->exists($params)) {
            $additionalParams = [];

            if ($indexSettings = $config->indexSettings()) {
                $additionalParams['body']['settings'] = $indexSettings;
            }

            if ($indexProperties = $config->indexProperties()) {
                $additionalParams['body']['mappings'][$type]['properties'] = $indexProperties;
            }

            $this->client->indices()->create(array_merge($params, $additionalParams));
        }

        foreach ($models as $model) {

            if (empty($searchableData = $model->toSearchableArray())) {
                return;
            }

            $objects['body'][] = [
                'index' => [
                    '_index' => $index,
                    '_id' => $model->getSearchKey(),
                    '_type' => $type
                ]
            ];

            $objects['body'][] = array_merge(['model' => get_class($model)], $searchableData);
        }

        if (!empty($objects)) {
            $this->client->bulk((array)$objects);
        }
    }

    /**
     * Remove the given model from the index.
     *
     * @param  Collection  $models
     * @return void
     */
    public function delete($models)
    {
        $config = $models->first();
        $type = $config->searchableAs();
        $index = "$this->indexPrefix-$type";

        foreach ($models as $model) {
            $objects['body'][] = array(
                'delete' => array(
                    '_index' => $index,
                    '_type' => $type,
                    '_id' => $model->getSearchKey()
                )
            );
        }

        if (!empty($objects)) {
            $this->client->bulk((array)$objects);
        }
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed  $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id')->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param Builder $builder
     * @param mixed $results
     * @param Model $model
     * @return SearchCollection
     */
    public function map(Builder $builder, $results, $model)
    {
        if (count($results['hits']['hits']) === 0) {
            return new SearchCollection([], $results);;
        }

        $objectIds = collect($results['hits']['hits'])->pluck('_id')->values()->all();
        $objectIdPositions = array_flip($objectIds);

        $models = $model->getSearchModelsByIds(
            $objectIds
        )->filter(function ($model) use ($objectIds) {
            return in_array($model->getSearchKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getSearchKey()];
        })->values();


        return new SearchCollection($models, $results);
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed  $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total'];
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param Model $config
     * @return void
     */
    public function flush($config)
    {
        $type = $config->searchableAs();
        $index = "$this->indexPrefix-$type";
        $params = [
            'index' => $index,
            'body' => [
                'query' => [
                    'match_all' => (object)null
                ]
            ]
        ];

        $this->client->deleteByQuery($params);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder, $builder->wheres);
    }

    /**
     * Execute search
     *
     * @param Builder $builder
     * @param array   $filters
     * @return array
     */
    protected function performSearch(Builder $builder, array $filters = [])
    {
        $type = $builder->model->searchableAs();
        $index = "$this->indexPrefix-$type";

        $params = [
            'index' => $index,
            'size' => $builder->limit,
            'from' => $builder->offset,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'query_string' => [
                                    'query' => $builder->query,
                                    'fields' => $builder->fields,
                                    'default_operator' => 'and',
                                    'fuzziness' => config('laravel-search.fuzziness'),
                                ],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if (empty($builder->query)) {
            $params['body']['query']['bool']['must'] = [
                [
                    'match_all' => (object)null
                ]
            ];
        }

        foreach ($filters as $field => $value) {
            $params['body']['query']['bool']['filter'][] =
                [
                    'term' => [
                        $field => $value,
                    ],
                ];
        }

        if ($sort = $builder->sort) {
            $params['body']['sort'] = $sort;
        }

        return $this->client->search($params);
    }

    /**
     * Get the filter array for the query.
     *
     * @param Builder $builder
     * @return array
     */
    protected function filters(Builder $builder)
    {
        return collect($builder->wheres)->map(function ($value, $key) {
            return [$key => $value];
        })->values()->all();
    }

    /**
     * Perform the given search on the engine for all indices
     *
     * @param AllBuilder $builder
     * @return mixed
     */
    public function searchAll(AllBuilder $builder)
    {
        $params = [
            'index' => "$this->indexPrefix-*",
            'size' => $builder->limit,
            'from' => $builder->offset,
            'body' => [
                'query' => [
                    'query_string' => [
                        'query' => $builder->query,
                        'default_operator' => 'and',
                        'fuzziness' => config('laravel-search.fuzziness'),
                    ],
                ]
            ]
        ];

        if (empty($builder->query)) {
            $params['body']['query'] = [
                'match_all' => (object)null
            ];

        }

        foreach ($this->filters($builder) as $field => $value) {
            $params['body']['filter']['bool']['must'] = [
                'term' => [$field => $value]
            ];
        }

        if ($sort = $builder->sort) {
            $params['body']['sort'] = $sort;
        }

        return $this->client->search($params);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @param $results
     * @return mixed
     */
    public function mapAll(Builder $builder, $results)
    {
        if (count($results['hits']['hits']) === 0) {
            return new SearchCollection([], $results);;
        }

        $order = 0;
        $grouped = [];

        foreach ($results['hits']['hits'] as $hit) {
            $grouped[$hit['_source']['model']][] = ['id' => $hit['_id'], 'order' => $order];
            $order++;
        }

        $return = [];

        foreach ($grouped as $class => $ids) {
            $model_ids = array_pluck($ids, 'id');
            $model = new $class;

            $models = $model->getSearchModelsByIds(
                $model_ids
            )->filter(function ($model) use ($model_ids) {
                return in_array($model->getSearchKey(), $model_ids);
            })->values();

            foreach ($models as $model) {
                foreach ($ids as $id) {
                    if ($id['id'] == $model->getKey()) {
                        $return[$id['order']] = $model;
                        break;
                    }
                }
            }
        }

        ksort($return);

        return new SearchCollection($return, $results);
    }

    /**
     * Remove the given models index
     *
     * @param $model
     * @return void
     */
    public function deleteIndex($config)
    {
        $type = $config->searchableAs();
        $index = "$this->indexPrefix-$type";

        $params = [
            'index' => $index
        ];

        if($this->client->indices()->exists($params)){
            $this->client->indices()->delete($params);
        }
    }
}
