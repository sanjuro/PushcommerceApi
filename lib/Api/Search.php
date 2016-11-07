<?php

namespace Pushcommerce\Api;

/**
 * Implement the Search API.
 *
 */
class Search extends AbstractApi
{
    /**
     * Search customers by filter (q).
     *
     * @param string $q     the filter
     * @param string $sort  the sort field
     * @param string $order asc/desc
     *
     * @return array list of customers found
     */
    public function customers($q, $sort = 'updated', $order = 'desc')
    {
        return $this->get('/search/customers', array('q' => $q, 'sort' => $sort, 'order' => $order));
    }

    /**
     * Search orders by filter (q).
     *
     * @param string $q     the filter
     * @param string $sort  the sort field
     * @param string $order asc/desc
     *
     * @return array list of orders found
     */
    public function orders($q, $sort = 'updated', $order = 'desc')
    {
        return $this->get('/search/orders', array('q' => $q, 'sort' => $sort, 'order' => $order));
    }

    /**
     * Search products by filter (q).
     *
     * @param string $q     the filter
     * @param string $sort  the sort field
     * @param string $order asc/desc
     *
     * @return array list of products found
     */
    public function products($q, $sort = 'updated', $order = 'desc')
    {
        return $this->get('/search/products', array('q' => $q, 'sort' => $sort, 'order' => $order));
    }
}