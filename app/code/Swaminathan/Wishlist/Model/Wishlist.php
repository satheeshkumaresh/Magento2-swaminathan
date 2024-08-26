<?php
namespace Swaminathan\Wishlist\Model;

use Swaminathan\Wishlist\Api\WishlistInterface;

/**
 * Class Wishlist
 * @package Swaminathan\Wishlist\Model
 */
class Wishlist extends \Magento\Wishlist\Model\Wishlist implements WishlistInterface
{

    /**
     * @inheritdoc
     */
    public function getItems()
    {
        return $this->getItemCollection()->getItems();
    }
}
