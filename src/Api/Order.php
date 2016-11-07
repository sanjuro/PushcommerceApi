<?php

namespace Pushcommerce\Api;

use Pushcommerce\Exception\MissingArgumentException;

/**
 * Orders class.
 *
 * @author Shadley Wentzel <shadley@pushcommerce.com>
 */
class Order extends AbstractApi
{
    /**
     * Find all orders.
     *
     * @return array list of orders found
     */
    public function all(array $params = array())
    {
        return $this->get('/orders/'.array_merge(array('page' => 1), $params));
    }
}