<?php

namespace Swaminathan\Reorder\Api;


interface ReorderInterface
{
    /**
    * Return Create reorder.
    *
    * 
    * @param int $orderId
    * @return array
    */

   public function reorderItem($orderId);

  
}