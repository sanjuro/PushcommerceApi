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
        return $this->get('/products/' . array_merge(array('page' => 1), $params));
    }

    /**
     * Get extended information about a prouduct
     *
     *
     * @param string $product_handle the product handle
     *
     * @return array information about the product
     */
    public function show($product_handle)
    {
        return $this->get('/products/' . rawurlencode($product_handle));
    }
}