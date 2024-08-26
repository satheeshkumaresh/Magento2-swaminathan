<?php 
namespace Swaminathan\AccountInformation\Api;
 
 
interface CustomerAccountInterface {
    /**
     * 
     * 
     * @return array
     */
    public function getCustomerId();
    
    /**
     * @param string[] $data
     * @return array
     */

    public function saveCustomerAccount($data);
     /**
     * 
     * 
     * @return array
     */

    public function getCustomerInformation();

}
