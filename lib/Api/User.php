<?php

namespace Pushcommerce\Api;

/**
 * @author Shadley Wentzel <shadley@pushcommerce.com>
 */
class User extends AbstractApi
{
    /**
     * Find all users.
     *
     * @return array list of users found
     */
    public function all(array $params = array())
    {
        return $this->get('/users/'.array_merge(array('page' => 1), $params));
    }
}