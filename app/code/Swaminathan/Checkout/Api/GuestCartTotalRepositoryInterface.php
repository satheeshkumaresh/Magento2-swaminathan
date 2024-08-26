<?php
namespace Swaminathan\Checkout\Api;

interface GuestCartTotalRepositoryInterface
{
    /**
     * Return quote totals data for a specified cart.
     *
     * @param string $cartId The cart ID.
     * @return \Magento\Quote\Api\Data\TotalsInterface Quote totals data.
     * @throws \Magento\Framework\Exception\NoSuchEntityException The specified cart does not exist.
     */
    public function get($cartId);
}