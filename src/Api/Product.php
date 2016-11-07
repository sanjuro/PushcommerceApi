<?php

namespace Pushcommerce\Api;

use Pushcommerce\Exception\MissingArgumentException;

/**
 * Proucts class.
 *
 * @author Shadley Wentzel <shadley@pushcommerce.com>
 */
class Product extends AbstractApi
{
    /**
     * Find all products.
     *
     * @return array list of products found
     */
    public function all(array $params = array())
    {
        return $this->get('/products/'.array_merge(array('page' => 1), $params));
    }
}