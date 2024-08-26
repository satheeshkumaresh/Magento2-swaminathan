<?php
namespace Swaminathan\Checkout\Api;

interface PlaceRazorpayOrderInterface
{
    /**
     * Set Razor Payment Details For Order
     *
     * @param mixed $data
     * @return string
     */
    public function placeRazorpayOrder($data);

    /**
     * Set Razor Payment Details For Order
     *
     * @param mixed $data
     * @return string
     */
    public function placeGuestRazorpayOrder($data);

}
