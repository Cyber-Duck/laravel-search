<?php

namespace CyberDuck\Search;

use CyberDuck\Search\Engines\ElasticSearchEngine;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Exception;
use Illuminate\Support\Manager;

class EngineManager extends Manager
{
    /**
     * Get a driver instance.
     *
     * @param string|null $name
     * @return mixed
     */
    public function engine($name = null)
    {
        return $this->driver($name);
    }

    /**
     * @return ElasticSearchEngine
     * @throws Exception
     */
    public function createEsDriver()
    {
        $this->ensureEsClientIsInstalled();

        $options['host'] = config('laravel-search.host');

        if ($user = config('laravel-search.es.user')) {
            $options['user'] = $user;
        }

        if ($password = config('laravel-search.es.password')) {
            $options['pass'] = $password;
        }

        if ($schema = config('laravel-search.es.schema')) {
            $options['schema'] = $schema;
        }

        if ($port = config('laravel-search.es.port')) {
            $options['port'] = $port;
        }

        return new ElasticSearchEngine(ClientBuilder::create()
            ->setHosts(count($options) === 1 ? $options : [$options])
            ->build(), config('laravel-search.index_prefix'));
    }

    /**
     * Ensure the Algolia API client is installed.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function ensureEsClientIsInstalled()
    {
        if (class_exists(Client::class)) {
            return;
        }

        throw new Exception('Please install the ElasticSearch client: elasticsearch/elasticsearch.');
    }

    /**
     * Get the default Scout driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->container['config']['laravel-search.driver'];
    }
}