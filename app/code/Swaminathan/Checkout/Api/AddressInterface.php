<?php
namespace Swaminathan\Checkout\Api;

interface AddressInterface
{
    /**
     * Get Address in checkout
     *
     * @param string $cartId The cart ID.
     * @return string
     */
    public function getAddress($cartId);

     /**
     * Get All Address in checkout
     *
     * @return string
     */
    public function getAllAddress();

}
