<?php
namespace Swaminathan\Cart\Api;
 
interface UpdateMultipleItems
{
    /**
     * @param string $cartId
     * @param mixed $cartItems
     * @return array
     */
 
    public function updateItems($cartId, $cartItems);
    
}
