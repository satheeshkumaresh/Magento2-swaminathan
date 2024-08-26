<?php
namespace Swaminathan\Wishlist\Api;

use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Interface ItemInterface
 * @package Swaminathan\Wishlist\Api
 * @api
 */
interface ItemInterface
{

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface
     */
    public function getProduct(): ProductInterface;
}
