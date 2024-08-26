<?php
namespace Swaminathan\Checkout\Api;

interface GuestShippingInformationManagementInterface
{
    /**
     * @param string $cartId
     * @param \Swaminathan\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return string
     */
    public function saveAddressInformation(
        $cartId,
        \Swaminathan\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    );
}
