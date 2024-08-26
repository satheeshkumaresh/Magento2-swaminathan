<?php

namespace Swaminathan\Customer\Model;

use Swaminathan\Customer\Api\CustomerAddressInterface;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as MagentoCountryCollection;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\ResourceModel\Region\Collection  as RegionCollection;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote\AddressFactory as QuoteAddressFactory;

class CustomerAddress implements CustomerAddressInterface
{
    const PAGE_LIMIT = 15;

    public function __construct(
        AddressFactory $addressFactory,
        Http $http,
        TokenFactory $tokenFactory,
        CustomerSession $customerSession,
        CustomerFactory $CustomerFactory,
        MagentoCountryCollection $magentoCountryCollection,
        Country $country,
        RegionCollection $regionCollection,
        SubscriberFactory $subscriberFactory,
        QuoteFactory $quoteFactory,
        QuoteAddressFactory $quoteAddressFactory
    ) {
        $this->addressFactory = $addressFactory;
        $this->http = $http;
        $this->tokenFactory = $tokenFactory;
        $this->customerFactory = $CustomerFactory;
        $this->magentoCountryCollection = $magentoCountryCollection;
        $this->country = $country;
        $this->regionCollection = $regionCollection;
        $this->subscriberFactory = $subscriberFactory;
        $this->quoteFactory = $quoteFactory;
        $this->quoteAddressFactory = $quoteAddressFactory;
    }
    public function validateMobile($phonenumber)
    {
        return preg_match('/^[0-9]{10}+$/', $phonenumber);
    }
    public function getCustomerId() {   
            $authorizationHeader = $this->http->getHeader("Authorization");
            $tokenParts = explode("Bearer", $authorizationHeader);
            $tokenPayload = trim(array_pop($tokenParts));
            /** @var Token $token */
            $token = $this->tokenFactory->create();
            $token->loadByToken($tokenPayload);
            $customerId = $token->getCustomerId();
            return $customerId;
    }
    // Get Quote id by customer id
    public function getQuoteIdByCustomerId($customerId)
    {
        $customer = $this->customerFactory->create()->load($customerId);
        $quote = $this->quoteFactory->create()->loadByCustomer($customer);
        return $quote->getId();
    }
    /**
     *
     * @param mixed $customer
     * @return array
     */
    public function saveCustomerAddress($customer)
    {
        $customerId = $this->getCustomerId();
        $quoteId = $this->getQuoteIdByCustomerId($customerId);
        //validate First Name
        if (!isset($customer["firstname"]) || empty($customer["firstname"])) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the First Name",
            ];
            return $response;
        }
        //validate Last Name
        if (!isset($customer["lastname"]) || empty($customer["lastname"])) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Last Name",
            ];
            return $response;
        }
        //street address
        if (
            !isset($customer["streetaddress"]) ||
            empty($customer["streetaddress"])
        ) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Street Address",
            ];
            return $response;
        }
        if (!isset($customer["country_id"]) || empty($customer["country_id"])) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Country",
            ];
            return $response;
        }
        if (!isset($customer["region_id"]) || empty($customer["region_id"])) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the State",
            ];
            return $response;
        }
        if (!isset($customer["city"]) || empty($customer["city"])) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the Cityname",
            ];
            return $response;
        }
        if (!isset($customer["zip_code"]) || empty($customer["zip_code"])) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Please Enter the PostalCode",
            ];
            return $response;
        }
        $firstname = $customer["firstname"];
        $lastname = $customer["lastname"];
        if(isset($customer['company'])){
            $company = $customer["company"];
        }
        $phonenumber = $customer["phonenumber"];
        $streetaddress = $customer["streetaddress"];
        $country_id = $customer["country_id"];
        $region = $this->getRegionNameById($customer["region_id"]);
        $region_id = $customer["region_id"];
        $city = $customer["city"];
        $zip_code = $customer["zip_code"];
        $defaultBilling = $customer["DefaultBilling"];
        $defaultShipping = $customer["DefaultShipping"];
        $address = $this->addressFactory->create();
        $address
            ->setCustomerId($customerId)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setCountryId($country_id)
            ->setRegion($region)
            ->setRegionId($region_id)
            ->setPostcode($zip_code)
            ->setCity($city)
            ->setTelephone($phonenumber)
            ->setStreet($streetaddress)
            ->setIsDefaultBilling($defaultBilling)
            ->setIsDefaultShipping($defaultShipping);
        if(isset($customer['company'])){
            $address->setCompany($company);
        }
        
        // Get customer address count
        $addressDatas = $this->addressFactory->create()->getCollection();
        $addressDatas->addFieldToFilter('parent_id', $customerId);
        $totalAddressCount = count($addressDatas->getData());
        // save the given address
        $address->save();
        // get last address fron customer
        $addressCollection = $this->addressFactory->create()->getCollection();
        $addressCollection->addFieldToFilter('parent_id', $customerId);
        $addressCollection->setOrder('entity_id', 'DESC');
        $addressCollection->setPageSize(1);
        $lastAddress = $addressCollection->getLastItem();
        $lastAddressId = $lastAddress->getId();
        $quoteAddressData = $this->quoteAddressFactory->create()
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('address_type', 'shipping');
        $quoteAddressId = $quoteAddressData->getData()[0]['address_id'];
        $quoteAddress = $this->quoteAddressFactory->create()->load($quoteAddressId, 'address_id');
        $customerAddressId = $quoteAddressData->getData()[0]['customer_address_id'];
        if($defaultShipping == 1 && ($customerAddressId != null || $totalAddressCount == 0)){
            $quoteAddress
                ->setCustomerId($customerId)
                ->setFirstname($firstname)
                ->setLastname($lastname)
                ->setCountryId($country_id)
                ->setRegion($region)
                ->setRegionId($region_id)
                ->setPostcode($zip_code)
                ->setCity($city)
                ->setTelephone($phonenumber)
                ->setStreet($streetaddress)
                ->setCustomerAddressId($lastAddressId);
            $quoteAddress->save();
        }
        $quoteBillingAddressData = $this->quoteAddressFactory->create()
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('address_type', 'billing');
        $quoteBillingAddressId = $quoteBillingAddressData->getData()[0]['address_id'];
        $quoteBillingAddress = $this->quoteAddressFactory->create()->load($quoteBillingAddressId, 'address_id');
        $customerBillingAddressId = $quoteBillingAddressData->getData()[0]['customer_address_id'];
        if($defaultBilling == 1 && ($customerBillingAddressId != null || $totalAddressCount == 0)){
            $quoteBillingAddress
                ->setCustomerId($customerId)
                ->setFirstname($firstname)
                ->setLastname($lastname)
                ->setCountryId($country_id)
                ->setRegion($region)
                ->setRegionId($region_id)
                ->setPostcode($zip_code)
                ->setCity($city)
                ->setTelephone($phonenumber)
                ->setStreet($streetaddress)
                ->setCustomerAddressId($lastAddressId);
            $quoteBillingAddress->save();
        }
        $response[] = [
            "code" => 200,
            "success" => true,
            "message" => "Address successfully added.",
        ];
        return $response;
    }
     // get region name by id
     public function getRegionNameById(int $id)
     {
         $region = $this->regionCollection->getItemById($id);
         return $region->getName();
     }
     /**
     *
     * @param mixed $pageSize
     * @param int $currPage
     * @param int $allAddress
     * @return array
     */
    public function getCustomerAddress($pageSize,$currPage,$allAddress)
    {
        $customerId = $this->getCustomerId();

        $billingAddressData = [];
        $shippingAddressData = [];
        $shippingAddreses = [];
        $shipping = [];
        $billing = [];
       $addressCollection = $this->addressFactory->create()->getCollection()->addFieldToSelect('*')->addFieldToFilter('parent_id',$customerId);
       $addressCount = count($addressCollection->getData());
        // Load customer factory by customer id
        $customer = $this->customerFactory->create()->load($customerId);
        // Get default billing address &&   Get default shipping address
        $billingAddressId = $customer->getDefaultBilling();
        $shippingAddressId = $customer->getDefaultShipping();
            // load billing address by address id
        $billingAddress = $this->addressFactory->create()->load($billingAddressId);
        $billingAddressDatas = $billingAddress->getData();
        
        if(!empty($billingAddressDatas)){
            $billingCollectionAddrId=$billingAddressDatas['entity_id'];
            $billingAddressData["address_id"] = $billingAddressDatas['entity_id'];
            $billingAddressData["customerid"] = $customerId;
            $billingAddressData['firstname'] = $billingAddressDatas['firstname'];
            $billingAddressData['lastname'] = $billingAddressDatas['lastname'];
            if($billingAddressDatas['company'] != null){
                $billingAddressData['company'] = $billingAddressDatas['company'];
            }
            else{
                $billingAddressData['company'] = "";
            }
            $streetAddress = [];
            $street = explode("\n",$billingAddressDatas['street']);
            foreach($street as $streetName){
                $streetAddress[] = $streetName;
            }
            $billingAddressData['streetaddress'] = $streetAddress;
            $billingAddressData['city'] = $billingAddressDatas['city'];
            $billingAddressData['region'] = $billingAddressDatas['region'];
            $billingAddressData['region_id'] = $billingAddressDatas['region_id'];
            $billingAddressData['telephone'] = $billingAddressDatas['telephone'];
            $billingAddressData['post_code'] = $billingAddressDatas['postcode'];
            $billingAddressData["country_id"] = $billingAddressDatas['country_id'];
            $billingAddressData['country_name'] = $this->getCountryname($billingAddressDatas['country_id']);
            if($billingAddressId == $billingCollectionAddrId){
                $billingAddressData["default_billing"] = 1; 
            }else{
                $billingAddressData["default_billing"] = 0;
            }
           if($shippingAddressId == $billingCollectionAddrId){
                $billingAddressData["default_shipping"] = 1;
            }else{
                $billingAddressData["default_shipping"] = 0;
            }
        }
        if(!empty($billingAddressData)){
            $billing[] = $billingAddressData;
        }
        $shippingAddress = $this->addressFactory->create()->load($shippingAddressId);
        $shippingAddressDatas = $shippingAddress->getData();
        if(!empty($shippingAddressDatas)){
            $shippingCollectionAddrId = $shippingAddress['entity_id'];
            $shippingAddressData["address_id"] = $shippingAddress['entity_id'];
            $shippingAddressData["customerid"] = $customerId;
            $shippingAddressData['firstname'] = $shippingAddress['firstname'];
            $shippingAddressData['lastname'] = $shippingAddress['lastname'];
            if( $shippingAddress['company'] != null){
                $shippingAddressData['company'] = $shippingAddress['company'];
            }
            else{
                $shippingAddressData['company'] = "";
            }
            $streetAddress = [];
            $street = explode("\n",$shippingAddress['street']);
            foreach($street as $streetName){
                $streetAddress[] = $streetName;
            }
            $shippingAddressData['streetaddress'] = $streetAddress;
            $shippingAddressData['city'] = $shippingAddress['city'];
            $shippingAddressData['region'] = $shippingAddress['region'];
            $shippingAddressData['telephone'] = $shippingAddress['telephone'];
            $shippingAddressData['post_code'] = $shippingAddress['postcode'];
            $shippingAddressData["country_id"] = $shippingAddress['country_id'];
            $shippingAddressData["region_id"] = $shippingAddress['region_id'];
            $shippingAddressData['country_name'] = $this->getCountryname($shippingAddress['country_id']);
            if($billingAddressId == $shippingCollectionAddrId){
                $shippingAddressData["default_billing"] = 1; 
            }else{
                $shippingAddressData["default_billing"] = 0;
            }
           if($shippingAddressId == $shippingCollectionAddrId){
                $shippingAddressData["default_shipping"] = 1;
            }else{
                $shippingAddressData["default_shipping"] = 0;
            }
        }
        if(!empty($shippingAddressData)){
            $shipping[] = $shippingAddressData;
        }
        $responseData['billing_address'] = $billing;
        $responseData['shipping_address'] = $shipping;
        $responseData['all_address'] = [];
        $addresses=$this->addressFactory->create()->getCollection()->addFieldToFilter('parent_id', $customerId);
      if($allAddress == 1){
        foreach($addresses as $address){
       $addressId = $address->getEntityId();
       if(($billingAddressId != $addressId) && ($shippingAddressId != $addressId)){
        $custom["address_id"] = $address->getEntityId();
            $custom["customerid"] = $address->getCustomerId();
            $custom["firstname"] = $address->getFirstname();
            $custom["lastname"] = $address->getLastname();
            if($address->getCompany() != null){
                $custom['company'] = $address->getCompany();
            }
            else{
                $custom['company'] = "";
            }
            $custom["country_id"] = $address->getCountryId();
            $country = $this->country->loadByCode($custom["country_id"]);
            $custom["country_name"] = $country->getName();
            $custom["region"] = $address->getRegion();
            $custom["region_id"] = $address->getRegionId();
            $custom["phone"] = $address->getTelephone();
            $custom["city"] = $address->getCity();
            $custom['streetaddress'] = $address->getStreet();
            $custom["zip_code"] = $address->getPostcode();
            if($billingAddressId == $custom["address_id"]){
                $custom["default_billing"] = 1; 
            }else{
                $custom["default_billing"] = 0;
            }
           if($shippingAddressId == $custom["address_id"]){
                $custom["default_shipping"] = 1;
            }else{
                $custom["default_shipping"] = 0;
            }
            $allData[] = $custom;
            $responseData['all_address']=$allData;
        }
        }
      }else{
        if($pageSize == 0 || $pageSize == ""){
            $addresses->setOrder(
                    'created_at',
                    'desc'
                )                                                                                  //set pagination   Recent  order
                    ->setPageSize(self::PAGE_LIMIT)
                    ->setCurPage($currPage);
            }else{
               $addresses->setOrder(
                    'created_at',
                    'desc'
                )                                                                                  //set pagination   Recent  order
                    ->setPageSize($pageSize)
                    ->setCurPage($currPage);
            }
        $data = [];   
        foreach ($addresses as $address) {
            $custom=[];
            $addressId=$address->getEntityId();
         if(($billingAddressId != $addressId) && ($shippingAddressId != $addressId))
            {$custom["address_id"] = $address->getEntityId();
            $custom["customerid"] = $address->getCustomerId();
            $custom["firstname"] = $address->getFirstname();
            $custom["lastname"] = $address->getLastname();
            if($address->getCompany() != null){
                $custom['company'] = $address->getCompany();
            }
            else{
                $custom['company'] = "";
            }
            $custom["country_id"] = $address->getCountryId();
            $country = $this->country->loadByCode($custom["country_id"]);
            $custom["country_name"] = $country->getName();
            $custom["region"] = $address->getRegion();
            $custom["region_id"] = $address->getRegionId();
            $custom["phone"] = $address->getTelephone();
            $custom["city"] = $address->getCity();
            $custom['streetaddress'] = $address->getStreet();
            $custom["zip_code"] = $address->getPostcode();
            if($billingAddressId == $custom["address_id"]){
                $custom["default_billing"] = 1; 
            }else{
                $custom["default_billing"] = 0;
            }
           if($shippingAddressId == $custom["address_id"]){
                $custom["default_shipping"] = 1;
            }else{
                $custom["default_shipping"] = 0;
            }
            $data[] = $custom;
        }
    }
        $responseData['additional_address'] = $data;
        $additionalAddressCount=count($responseData['additional_address']);
}
        if ($responseData) {
            $respons[] = ["code" => 200, "success" => true, "count" => $additionalAddressCount, "show_per" =>$pageSize,"page" => $currPage ,"data" => $responseData, 
            ];
        } else {
            $respons[] = [
                "code" => 200,
                "success" => true,
                "message" => "Address Not Found",
                "data"=>$data
            ];
        }
        return $respons;
    }
    // Get Country name by Country Code
    public function getCountryname($countryCode){    
        $country = $this->country->loadByCode($countryCode);
        return $country->getName();
    }
    public function getCountryList()
    {
        $country = $this->magentoCountryCollection->create();
        $countries = $country->toOptionArray();
        foreach ($countries as $singleCountry) {
            if ($singleCountry["value"]) {
                $countryList[] = $singleCountry;
            }
        }
        $respons[] = [
            "code" => 200,
            "success" => true,
            "Country_code" => $countryList,
        ];
        return $respons;
    }
    /**
     *
     * @param string $countryCode
     * @return array
     */
    public function getStateList($countryCode)
    {
        $regionCollection = $this->country
            ->loadByCode($countryCode)
            ->getRegions();
        $regions = $regionCollection->loadData()->toOptionArray(false);
        $returnRegion = [];
        foreach ($regions as $eachRegion) {
            if ($eachRegion["value"]) {
                $returnRegion[] = $eachRegion;
            }
        }
        return $returnRegion;
    }
    /**
     *
     * @param mixed $customer
     * @param int $addressId
     * @return array
     */
    public function updateCustomerAddress($customer, $addressId)
    {
        $customerId = $this->getCustomerId();
        $quoteId = $this->getQuoteIdByCustomerId($customerId);
        $existAddress = $this->addressFactory->create()->load($addressId);
        if(!empty($existAddress->getData())){
        //validate First Name
            if (!isset($customer["firstname"]) || empty($customer["firstname"])) {
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Please Enter the First Name",
                ];
                return $response;
            }

            //validate Last Name
            if (!isset($customer["lastname"]) || empty($customer["lastname"])) {
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Please Enter the Last Name",
                ];
                return $response;
            }
            //street address
            if (!isset($customer["streetaddress"]) || empty($customer["streetaddress"])) {
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Please Enter the Street Address",
                ];
                return $response;
            }
            if (!isset($customer["country_id"]) || empty($customer["country_id"])) {
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Please Enter the Country",
                ];
                return $response;
            }
            if (!isset($customer["city"]) || empty($customer["city"])) {
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Please Enter the Cityname",
                ];
                return $response;
            }
            if (!isset($customer["zip_code"]) || empty($customer["zip_code"])) {
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Please Enter the PostalCode",
                ];
                return $response;
            }
            $firstname = $customer["firstname"];
            $lastname = $customer["lastname"];
            if(isset($customer["company"])){
                $company = $customer["company"];
            }
            $phonenumber = $customer["phonenumber"];
            $streetaddress = $customer["streetaddress"];
            $country_id = $customer["country_id"];
            $region = $this->getRegionNameById($customer["region_id"]);;
            $region_id = $customer["region_id"];
            $city = $customer["city"];
            $zip_code = $customer["zip_code"];
            $DefaultBilling = $customer["DefaultBilling"];
            $DefaultShipping = $customer["DefaultShipping"];
            $customerId = $this->getCustomerId();
            $addressData = $this->addressFactory->create()->load($addressId);
            $addressDataCustomerId = $addressData->getCustomerId();
            if ($customerId == $addressDataCustomerId) {
                $address = $this->addressFactory->create()->load($addressId);
                $address
                    ->setFirstname($firstname)
                    ->setLastname($lastname)
                    ->setCountryId($country_id)
                    ->setRegion($region)
                    ->setRegionId($region_id)
                    ->setPostcode($zip_code)
                    ->setCity($city)
                    ->setTelephone($phonenumber)
                    ->setStreet($streetaddress)
                    ->setIsDefaultBilling($DefaultBilling)
                    ->setIsDefaultShipping($DefaultShipping);
                if(isset($customer['company'])){
                    $address->setCompany($company);
                }
                $address->save();
                 // set shipping address in quote address table
                $quoteAddressData = $this->quoteAddressFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('quote_id', $quoteId)
                    ->addFieldToFilter('address_type', 'shipping');
                $quoteAddressId = $quoteAddressData->getData()[0]['address_id'];
                $quoteAddress = $this->quoteAddressFactory->create()->load($quoteAddressId, 'address_id');
                $customerAddressId = $quoteAddressData->getData()['0']['customer_address_id'];
                if($DefaultShipping == 1 && $customerAddressId != null && (!empty($customerAddressId))){
                    $quoteAddress
                        ->setCustomerId($customerId)
                        ->setFirstname($firstname)
                        ->setLastname($lastname)
                        ->setCountryId($country_id)
                        ->setRegion($region)
                        ->setRegionId($region_id)
                        ->setPostcode($zip_code)
                        ->setCity($city)
                        ->setTelephone($phonenumber)
                        ->setStreet($streetaddress)
                        ->setCustomerAddressId($addressId);
                    $quoteAddress->save();
                }
                // set billing address in quote address table
                $quoteBillingAddressData = $this->quoteAddressFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('quote_id', $quoteId)
                    ->addFieldToFilter('address_type', 'billing');
                $quoteBillingAddressId = $quoteBillingAddressData->getData()[0]['address_id'];
                $quoteBillingAddress = $this->quoteAddressFactory->create()->load($quoteBillingAddressId, 'address_id');
                $customerBillingAddressId = $quoteBillingAddressData->getData()['0']['customer_address_id'];
                if($DefaultBilling == 1 && $customerBillingAddressId != null && (!empty($customerBillingAddressId))){
                    $quoteBillingAddress
                        ->setCustomerId($customerId)
                        ->setFirstname($firstname)
                        ->setLastname($lastname)
                        ->setCountryId($country_id)
                        ->setRegion($region)
                        ->setRegionId($region_id)
                        ->setPostcode($zip_code)
                        ->setCity($city)
                        ->setTelephone($phonenumber)
                        ->setStreet($streetaddress)
                        ->setCustomerAddressId($addressId);
                    $quoteBillingAddress->save();
                }
                
                $response[] = [
                    "code" => 200,
                    "success" => true,
                    "message" => "Address Updated successfully",
                ];
            } else {
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "The requested customer is mismatch.",
                ];
            }
        }
        else{
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "The requested address is not found.",
            ];
        }
        return $response;
    }
    /**
     *
     * @param int $addressId
     * @return array
     */
    public function deleteCustomerAddress($addressId)
    {
        if (!$addressId || !isset($addressId) || empty($addressId)) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Invalid Address Id ",
            ];
            return $response;
        }   
        $customerId = $this->getCustomerId();
        // get quote id by customer id
        $quoteId = $this->getQuoteIdByCustomerId($customerId);
        // check quote id and customer address id is exist or not in quote address
        $quoteAddressData = $this->quoteAddressFactory->create()
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('customer_address_id', $addressId);
        if(count($quoteAddressData->getData()) > 0){
            foreach($quoteAddressData->getData() as $quoteAddress){
                $quoteAddressId =  $quoteAddress['address_id'];
                // load quote address model factory
                $quoteAddressData = $this->quoteAddressFactory->create();
                // load customer address id
                $quoteAddressData->load($quoteAddressId, 'address_id');
                // set null value in unknown customer address
                $quoteAddressData->setCustomerAddressId(NUll);
                // save the data
                $quoteAddressData->save();
            }
        }
        $address = $this->addressFactory->create()->load($addressId);
        $AddressDataCustomerId = $address->getCustomerId();
        if ($customerId == $AddressDataCustomerId) {
            $address->delete();
            $response[] = [
                "code" => 200,
                "success" => true,
                "message" => "Address Deleted successfully",
            ];
        }
        else{
        $response[] = [
            "code" => 400,
            "success" => false,
            "message" => "Invalid Address Id ",
        ];
        }
        
        return $response;
    }
    /**
     *
     *
     * @param int $addressId
     * @return array
     */
    public function customerEditAddress($addressId)
    {
        if (!$addressId) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Invalid  AddressId",
            ];
            return $response;
        }
        $customerId = $this->getCustomerId();
        $addressData = $this->addressFactory->create()->load($addressId);
        $addressDataCustomerId = $addressData->getCustomerId();
        $customer = $this->customerFactory->create()->load($customerId);
        // Get default billing address &&   Get default shipping address
        $billingAddressId = $customer->getDefaultBilling();
        $shippingAddressId = $customer->getDefaultShipping();
        $data=[];
        if ($customerId == $addressDataCustomerId) {
            $custom["entity_id"] = $addressData->getEntityId();
            $custom["customerid"] = $addressData->getCustomerId();
            $custom["firstname"] = $addressData->getFirstname();
            $custom["lastname"] = $addressData->getLastname();
            if($addressData->getCompany() != null){
                $custom['company'] = $addressData->getCompany();
            }
            else{
                $custom['company'] = "";
            }
            $custom["country_id"] = $addressData->getCountryId();
            $country = $this->country->loadByCode($custom["country_id"]);
            $custom["country_name"] = $country->getName();
            $custom["region"] = $addressData->getRegion();
            $custom["region_id"] = $addressData->getRegionId();
            $custom["phone"] = $addressData->getTelephone();
            $custom["city"] = $addressData->getCity();
            $custom["streetaddress"] =$addressData->getStreet();
            $custom["zip_code"] = $addressData->getPostcode();
            if($billingAddressId == $custom["entity_id"]){
                $custom["default_billing"] = 1; 
            }else{
                $custom["default_billing"] = 0;
            }
           if($shippingAddressId == $custom["entity_id"]){
                $custom["default_shipping"] = 1;
            }else{
                $custom["default_shipping"] = 0;
            }
            $data[] = $custom;
            $response[] = ["code" => 200, "success" => true, "data" => $data];
        } else {
            $response[] = [
                "code" => 200,
                "success" => true,
                "message" => "Address Not Found",
                "data" => $data
            ];
        }
        return $response;
    }
     /**
     *
     * @param mixed $data
     * @param int $addressId
     * @return array
     */
    public function sameAsBillingShippingAddress($addressId,$data){
        if (!$addressId || !isset($addressId) || empty($addressId)) {
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Invalid Address Id ",
            ];
        return $response;
        }   
        $customerId = $this->getCustomerId();
        $addressData = $this->addressFactory->create()->load($addressId);
        $addressDataCustomerId = $addressData->getCustomerId();
        $customer = $this->customerFactory->create()->load($customerId);
        // Get default billing address &&   Get default shipping address
        $billingAddressId = $customer->getDefaultBilling();
        $shippingAddressId = $customer->getDefaultShipping();
        if($data['saveaddress'] == 1){
            if ($customerId == $addressDataCustomerId) {
                $customerAddressGetData = $this->customerEditAddress($addressId);
                $getAddressStatus = $customerAddressGetData[0]['data'];
                $billingAddressStatus = $getAddressStatus[0]['default_billing'];
                $shippingAddressStatus = $getAddressStatus[0]['default_shipping'];
                $address = $this->addressFactory->create();          
                $address->setCustomerId($addressData->getCustomerId())
                    ->setFirstname($addressData->getFirstname())
                    ->setLastname($addressData->getLastname())
                    ->setCountryId($addressData->getCountryId())
                    ->setRegion($addressData->getRegion())
                    ->setRegionId($addressData->getRegionId())
                    ->setPostcode( $addressData->getPostcode())
                    ->setCity($addressData->getCity())
                    ->setTelephone($addressData->getTelephone())
                    ->setStreet($addressData->getStreet())
                    ->setIsDefaultBilling($data['defaultbilling'])
                    ->setIsDefaultShipping($data['defaultshipping']);
            if($addressData->getCompany() != null){
                $address->setCompany($addressData->getCompany());
            }
           $address->save();
           $response[] = [
            "code" => 200,
            "success" => true,
            "message" => "Address successfully added.",
           ];
            }else{
                $response[] = [
                    "code" => 400,
                    "success" => false,
                    "message" => "Address added failed.",
                ];
            }
        }else{
            if($customerId == $addressDataCustomerId){  
                    $address = $this->addressFactory->create()->load($addressId);
                    $address->setIsDefaultBilling($data['defaultbilling'])
                            ->setIsDefaultShipping($data['defaultshipping']);
                    $address->save();
                        $response[] = [
                            "code" => 200,
                            "success" => true,
                            "message" => "Address saved successfully.",
                        ];                 
            }else{
            $response[] = [
                "code" => 400,
                "success" => false,
                "message" => "Invalid Address Id ",
            ];
            }
      }   
    return $response;
    }
}