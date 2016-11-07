<?php

namespace Pushcommerce\Api;

use Pushcommerce\Exception\MissingArgumentException;

/**
 * Store class.
 *
 * @author Shadley Wentzel <shadley@pushcommerce.com>
 */
class Store extends AbstractApi
{
    /**
     * Get extended information about a store.
     *
     *
     * @param string $storename   the storename
     *
     * @return array information about the store
     */
    public function show($storename, $repository, $id)
    {
        return $this->get('/store/'.rawurlencode($storename));
    }
}