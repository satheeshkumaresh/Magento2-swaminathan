<?php
namespace Swaminathan\Checkout\Api;

interface ShippingInformationManagementInterface
{
    /**
     * @param int $cartId
     * @param \Swaminathan\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return string
     */
    public function saveAddressInformation(
        $cartId,
        \Swaminathan\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    );
}

