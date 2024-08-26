<?php 
namespace Swaminathan\Customer\Api;
 
 
interface DeleteCustomerAddressInterface {




	/**
	 * @param int $addressId
     * @return array
     */
	
    public function deleteCustomerAddressById($addressId);

}
