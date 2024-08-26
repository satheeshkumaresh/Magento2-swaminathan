<?php
namespace Swaminathan\Cart\Api;
 
interface DeleteAllCart
{
     /**
     * Removes the specified item from the specified cart.
     *
     * @param int $cartId The cart ID.
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified item or cart does not exist.
     * @throws \Magento\Framework\Exception\CouldNotSaveException The item could not be removed.
     */
    public function deleteAllCart($cartId);
}