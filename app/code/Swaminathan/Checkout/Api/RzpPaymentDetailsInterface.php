<?php
namespace Swaminathan\Checkout\Api;

interface RzpPaymentDetailsInterface
{
    /**
     * Set Razor Payment Details For Order
     * @param mixed $shipping_address
     * @param mixed $billing_address
     * @param mixed $data
     * @return string
     */
    public function setRzpPaymentDetailsForOrder($shipping_address, $billing_address, $data);

     /**
     * Set Razor Payment Details For Order
     * @param mixed $data
     * @return string
     */
    public function setGuestRzpPaymentDetailsForOrder($data);
}
