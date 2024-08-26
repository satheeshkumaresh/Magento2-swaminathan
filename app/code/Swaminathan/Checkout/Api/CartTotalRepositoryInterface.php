<?php
namespace Swaminathan\Checkout\Api;

interface CartTotalRepositoryInterface
{
    /**
     * Returns quote totals data for a specified cart.
     *
     * @param int $cartId The cart ID.
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function get($cartId);
}
