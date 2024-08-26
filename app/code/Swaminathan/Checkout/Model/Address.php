<?php
namespace Swaminathan\Checkout\Model;

use Magento\Quote\Model\Quote\AddressFactory as CheckoutAddressFactory;
use Swaminathan\Cart\Model\ShippingBillingAddress;
use Swaminathan\Customer\Model\CustomerAddress;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Quote\Api\CartRepositoryInterface;

class Address implements \Swaminathan\Checkout\Api\AddressInterface
{
    protected $checkoutAddressFactory;

    protected $shippingBillingAddress;

    protected $customerAddress;

    protected $addressFactory;

    protected $customerFactory;

    protected $quoteRepository;

    public function __construct(
        CheckoutAddressFactory $checkoutAddressFactory,
        ShippingBillingAddress $shippingBillingAddress,
        CustomerAddress $customerAddress,
        AddressFactory $addressFactory,
        CustomerFactory $customerFactory,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->checkoutAddressFactory = $checkoutAddressFactory;
        $this->shippingBillingAddress = $shippingBillingAddress;
        $this->customerAddress = $customerAddress;
        $this->addressFactory = $addressFactory;
        $this->customerFactory = $customerFactory;
        $this->quoteRepository = $quoteRepository;
    }
    public function getAddress($cartId){
        // get customer id from bearer token.
        $customerId = $this->customerAddress->getCustomerId();
        $data = $this->address($cartId);
        // get Address from (new / old) in quote 
        $quote = $this->quoteRepository->getActive($cartId);
        $data['address_from'] = $quote->getAddressFrom();
        if(!empty($customerId)){
            $data['all_address'] = $this->getAllAddress($customerId);
        }
        $response[] = [
            'code' => 200,
            'status' => true, 
            'data' => $data
        ];
        return $response;
    }

    public function address($cartId){
        $addressDetails = [];
        $countryName = "";
        $adddress = $this->checkoutAddressFactory->create()->getCollection();
        $adddress->addFieldToFilter('quote_id', $cartId);   
        foreach($adddress->getData() as $adddresses){
            $customerId = "";
            if( $adddresses['customer_id'] != null){
                $customerId  = $adddresses['customer_id'];
            }
            $customerAddressId = "";
            if( $adddresses['customer_address_id'] != null){
                $customerAddressId  = $adddresses['customer_address_id'];
            }
            $middleName = "";
            if( $adddresses['middlename'] != null){
                $middleName  = $adddresses['middlename'];
            }
            $addressInfo['address_id'] = $adddresses['address_id'];
            $addressInfo['quote_id'] = $adddresses['quote_id'];
            $addressInfo['address_type'] = $adddresses['address_type'];
            $addressInfo['customer_id'] = $customerId;
            $addressInfo['save_in_address_book'] = $adddresses['save_in_address_book'];
            $addressInfo['customer_address_id'] = $customerAddressId;
            $addressInfo['customer_address_id'] = $adddresses['customer_address_id'];
            $addressInfo['email'] = $adddresses['email'];
            $addressInfo['firstname'] = $adddresses['firstname'];
            $addressInfo['middlename'] = $middleName;
            $addressInfo['lastname'] = $adddresses['lastname'];
            $addressInfo['company'] = $adddresses['company'];
            $streetAddress = [];
            if(!empty($adddresses['street'])){
                $street = explode("\n",$adddresses['street']);
                foreach($street as $streetName){
                    $streetAddress[] = $streetName;
                }
            }
            $addressInfo['street'] = $streetAddress;
            $addressInfo['company'] = $adddresses['company'];
            $addressInfo['city'] = $adddresses['city'];
            $addressInfo['region'] = $adddresses['region'];
            $addressInfo['region_id'] = $adddresses['region_id'];
            $addressInfo['postcode'] = $adddresses['postcode'];
            $addressInfo['country_id'] = $adddresses['country_id'];
            if(!empty($adddresses['country_id'])){
                $countryName = $this->shippingBillingAddress->getCountryname($adddresses['country_id']);
            }
            $addressInfo['country_name'] = $countryName;
            $addressInfo['telephone'] = $adddresses['telephone'];
            $addressInfo['same_as_billing'] = $adddresses['same_as_billing'];
            $addressDetails[] = $addressInfo;
        }
        $data = [];
        $data['shipping_address'] = [];
        $data['billing_address'] = [];
        foreach($addressDetails as $addressDetails){
            if($addressDetails['address_type'] == "shipping"){
                $data['shipping_address'] = $addressDetails;
            }
            if($addressDetails['address_type'] == "billing"){
                $data['billing_address'] =  $addressDetails;
            }
        }
        return $data;
    }

    /**
     * Get All Address in checkout
     *
     * @return string
     */
    public function getAllAddress(){
        $addresses = [];
        // Get Customer id by bearer token
        $customerId = $this->customerAddress->getCustomerId();
        $customer = $this->customerFactory->create()->load($customerId);
        $billingAddressId = $customer->getDefaultBilling();
        $shippingAddressId = $customer->getDefaultShipping();
        $customerAddress = array();
        foreach ($customer->getAddresses() as $address)
        {
            $customerAddress[] = $address->toArray();
        }
        foreach ($customerAddress as $customerAddres) 
        {
            $addressData['address_id'] = $customerAddres['entity_id'];
            $addressData['customerid'] = $customerAddres['customer_id'];
            $addressData['firstname'] = $customerAddres['firstname'];
            $addressData['lastname'] = $customerAddres['lastname'];
            $addressData['company'] = $customerAddres['company'];
            $addressData['country_id'] = $customerAddres['country_id'];
            $countryname = "";
            if(!empty($customerAddres['country_id']) || $customerAddres['country_id'] != null){
                $countryname = $this->shippingBillingAddress->getCountryname($customerAddres['country_id']);
            }
            $addressData['country_name'] = $countryname;
            $addressData['region'] = $customerAddres['region'];
            $addressData['region_id'] = $customerAddres['region_id'];
            $addressData['phone'] = $customerAddres['telephone'];
            $addressData['city'] = $customerAddres['city'];
            $streetAddress = [];
            if(!empty($customerAddres['street'])){
                $street = explode("\n",$customerAddres['street']);
                foreach($street as $streetName){
                    $streetAddress[] = $streetName;
                }
            }
            $addressData['streetaddress'] = $streetAddress;
            $addressData['zip_code'] = $customerAddres['postcode'];
            $defaultBilling = 0;
            if($billingAddressId == $customerAddres['entity_id']){
                $defaultBilling = 1;
            }
            $defaultShipping = 0;
            if($shippingAddressId == $customerAddres['entity_id']){
                $defaultShipping = 1;
            }
            $addressData['default_billing'] = $defaultBilling;
            $addressData['default_shipping'] = $defaultShipping;
            $addresses[] = $addressData;
        }
        return $addresses;
    }
}
