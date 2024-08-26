<?php 
namespace Swaminathan\Customer\Api;
 

interface  CustomerTokenInterface 
{
    /** 
    * 
    * @param int $customerId
    * @return  array
    */
    public function customerToken($customerId);
    
}