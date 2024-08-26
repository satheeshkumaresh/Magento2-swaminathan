<?php

namespace Swaminathan\Wishlist\Api;


/**
 * @package Swaminathan\Wishlist\Api
 * @api
 */
interface GetWishlistInterface
{

    /**
     * @param mixed $pageSize
     * @param int $currPage
     * @return array
     */
    public function getCurrentWishlist($pageSize,$currPage);
}