<?php
namespace Swaminathan\Cart\Api;

interface CreateCartInterface
{
    /**
     * Create empty cart for customer
     * @return string
     */
    public function createEmptyCustomerCart();

}