<?php
namespace Swaminathan\Wishlist\Api;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface WishlistRepositoryInterface
 * @package Swaminathan\Wishlist\Api
 * @api
 */
interface WishlistRepositoryInterface
{

    /**
     * Get the current customers wishlist
     *
     * @return \Swaminathan\Wishlist\Api\WishlistInterface
     * @throws NoSuchEntityException
     */
    public function getCurrent(): WishlistInterface;

    /**
     * Add an item from the customers wishlist
     *
     * @param string $sku
     * @return bool
     */
    public function addItem(string $sku): bool;

    /**
     * Remove an item from the customers wishlist
     *
     * @param int $itemId
     * @return boolean
     * @throws NoSuchEntityException
     */
    public function removeItem(int $itemId);
}
