<?php
namespace Swaminathan\Checkout\Api;

interface ResetCartInterface
{
    /**
     * Reset Cart
     *
     * @param int $order_id
     * @return string
     */
    public function resetCart($order_id);

}
