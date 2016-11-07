<?php

namespace Pushcommerce\Api;

use Pushcommerce\Exception\MissingArgumentException;

/**
 * Customer Class.
 *
 * @author Shadley Wentzel <shadley@pushcommerce.com>
 */
class Customer extends AbstractApi
{
    /**
     * Find all customers.
     *
     * @return array list of customers found
     */
    public function all(array $params = array())
    {
        return $this->get('/customers/'.array_merge(array('page' => 1), $params));
    }
}