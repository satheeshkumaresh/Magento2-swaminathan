<?php
namespace Swaminathan\Cart\Api;

interface ShippingBillingAddresstInterface
{
    /**
     * @param string $customerId
     * @return string
     */
 
    public function getShippingBillingAddress($customerId);

}
