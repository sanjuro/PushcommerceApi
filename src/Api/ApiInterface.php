<?php

namespace Pushcommerce\Api;

/**
 * Api interface.
 *
 */
interface ApiInterface
{
    public function getPerPage();

    public function setPerPage($perPage);
}