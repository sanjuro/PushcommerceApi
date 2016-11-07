<?php

namespace Pushcommerce;

use Pushcommerce\Api\ApiInterface;
use Pushcommerce\Api\Search;
use Pushcommerce\HttpClient\Message\ResponseMediator;

/**
 * Pager class for supporting pagination
 *
 */
class ResultPager implements ResultPagerInterface
{
    /**
     * The Pushcommerce Client to use for pagination.
     *
     * @var \Pushcommerce\Client
     */
    protected $client;

    /**
     * Comes from pagination headers in Pushcommerce API results.
     *
     * @var array
     */
    protected $pagination;

    /**
     * The Pushcommerce client to use for pagination.
     *
     * This must be the same instance that you got the Api instance from.
     *
     * Example code:
     *
     * $client = new \Pushcommerce\Client();
     * $api = $client->api('someApi');
     * $pager = new \Pushcommerce\ResultPager($client);
     *
     * @param \Pushcommerce\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function getPagination()
    {
        return $this->pagination;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(ApiInterface $api, $method, array $parameters = array())
    {
        $result = $this->callApi($api, $method, $parameters);
        $this->postFetch();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll(ApiInterface $api, $method, array $parameters = array())
    {
        $isSearch = $api instanceof Search;

        // get the perPage from the api
        $perPage = $api->getPerPage();

        // set parameters per_page to Pushcommerce max to minimize number of requests
        $api->setPerPage(100);

        $result = $this->callApi($api, $method, $parameters);
        $this->postFetch();

        if ($isSearch) {
            $result = isset($result['items']) ? $result['items'] : $result;
        }

        while ($this->hasNext()) {
            $next = $this->fetchNext();

            if ($isSearch) {
                $result = array_merge($result, $next['items']);
            } else {
                $result = array_merge($result, $next);
            }
        }

        // restore the perPage
        $api->setPerPage($perPage);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function postFetch()
    {
        $this->pagination = ResponseMediator::getPagination($this->client->getLastResponse());
    }

    /**
     * {@inheritdoc}
     */
    public function hasNext()
    {
        return $this->has('next');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchNext()
    {
        return $this->get('next');
    }

    /**
     * {@inheritdoc}
     */
    public function hasPrevious()
    {
        return $this->has('prev');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchPrevious()
    {
        return $this->get('prev');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchFirst()
    {
        return $this->get('first');
    }

    /**
     * {@inheritdoc}
     */
    public function fetchLast()
    {
        return $this->get('last');
    }

    /**
     * {@inheritdoc}
     */
    protected function has($key)
    {
        return !empty($this->pagination) && isset($this->pagination[$key]);
    }

    /**
     * {@inheritdoc}
     */
    protected function get($key)
    {
        if ($this->has($key)) {
            $result = $this->client->getHttpClient()->get($this->pagination[$key]);
            $this->postFetch();

            return ResponseMediator::getContent($result);
        }
    }

    /**
     * @param ApiInterface $api
     * @param $method
     * @param array $parameters
     *
     * @return mixed
     */
    protected function callApi(ApiInterface $api, $method, array $parameters)
    {
        return call_user_func_array(array($api, $method), $parameters);
    }
}