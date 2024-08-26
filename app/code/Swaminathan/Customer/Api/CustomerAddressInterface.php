<?php 
namespace Swaminathan\Customer\Api;
 
 
interface CustomerAddressInterface {

	/**
     * 
     * 
     * @return array
     */
    public function getCustomerId();
    
    /**
     * 
     * @param mixed $customer
     * 
     * @return array
     */
    public function saveCustomerAddress($customer);

    /**
     * 
     * @param int $allAddress
     * @param mixed $pageSize
     * @param int $currPage
     * @return array
     */
    public function getCustomerAddress($pageSize,$currPage,$allAddress);

    /**
     * 
     * 
     * @return array
     */
    public function getCountryList();
      /**
     * 
     * @param string  $countryCode
     * @return array
     */
    public function getStateList( $countryCode);
    /**
     * 
     * @param mixed $customer
     * @param int $addressId
     * @return array
     */
    public function updateCustomerAddress($customer,$addressId);
    /**
     * 
     * @param int $addressId
     * @return array
     */
    public function deleteCustomerAddress($addressId);
    /**
     * 
     * 
     * @param int $addressId
     * @return array
     */
    public function customerEditAddress($addressId);
/**
     *
     *@param mixed $data
     * @param int $addressId
     * @return array
     */
    public function sameAsBillingShippingAddress($addressId,$data);


}
