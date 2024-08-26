<?php 
namespace Swaminathan\AccountInformation\Api;
 
 

interface ViewOrderInterface
{
/**
* GET for Post api
* @return boolean|array
* @param string $orderId .
*/

public function viewOrder($orderId);
}
